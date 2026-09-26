<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAuthProvider;
use App\Services\OidcTokenVerifier;
use App\Services\SocialLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialLoginService $socialLoginService,
        private readonly OidcTokenVerifier $tokenVerifier,
    ) {}

    /**
     * Start a state- and PKCE-protected social authorization request.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $configuration = $this->configuration($provider);

        if (! $this->isAvailable($provider, $configuration)) {
            return redirect()->route('login')->withErrors(['email' => ucfirst($provider).' login is not available.']);
        }

        $state = Str::random(64);
        $verifier = Str::random(96);
        $nonce = Str::random(64);
        $redirectUri = $this->redirectUri($provider, $configuration);

        $request->session()->put("social_auth.{$provider}", [
            'state' => $state,
            'verifier' => $verifier,
            'nonce' => $nonce,
            'redirect_uri' => $redirectUri,
            'issued_at' => now()->timestamp,
        ]);

        $parameters = [
            'client_id' => $configuration['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $provider === 'google' ? 'openid email profile' : 'openid profile',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $this->base64UrlEncode(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256',
        ];

        if ($provider === 'google') {
            $parameters['prompt'] = 'select_account';
        }

        return redirect()->away($configuration['authorization_url'].'?'.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * Exchange the provider code and authenticate the verified TrendShop user.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $configuration = $this->configuration($provider);
        $flow = $request->session()->pull("social_auth.{$provider}");

        if (! $this->isAvailable($provider, $configuration)) {
            return redirect()->route('login')->withErrors(['email' => ucfirst($provider).' login is not available.']);
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['email' => ucfirst($provider).' login was cancelled.']);
        }

        if (! is_array($flow)
            || ! is_string($request->query('state'))
            || ! hash_equals((string) ($flow['state'] ?? ''), $request->query('state'))
            || (int) ($flow['issued_at'] ?? 0) < now()->subMinutes(10)->timestamp
            || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['email' => 'The social login request expired or is invalid. Please try again.']);
        }

        try {
            $identity = $provider === 'google'
                ? $this->googleIdentity($request->string('code')->toString(), $flow, $configuration)
                : $this->telegramIdentity($request->string('code')->toString(), $flow, $configuration);

            $user = $this->socialLoginService->resolve($provider, $identity, app()->getLocale());

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended(route('profile'))->with('success', 'Welcome back to TrendShop.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Social login failed.', [
                'provider' => $provider,
                'exception' => $exception::class,
            ]);

            return redirect()->route('login')->withErrors(['email' => ucfirst($provider).' could not verify your account. Please try again.']);
        }
    }

    /** @return array{id: string, name: string, email: ?string, email_verified: bool, avatar: ?string, metadata: array<string, mixed>} */
    private function googleIdentity(string $code, array $flow, array $configuration): array
    {
        $tokens = Http::asForm()
            ->connectTimeout(3)
            ->timeout(10)
            ->retry([100, 300])
            ->post($configuration['token_url'], [
                'client_id' => $configuration['client_id'],
                'client_secret' => $configuration['client_secret'],
                'code' => $code,
                'code_verifier' => $flow['verifier'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $flow['redirect_uri'],
            ])
            ->throw()
            ->json();

        $profile = Http::withToken((string) ($tokens['access_token'] ?? ''))
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->retry([100, 300])
            ->get($configuration['userinfo_url'])
            ->throw()
            ->json();

        if (! is_string($profile['sub'] ?? null) || ! is_string($profile['name'] ?? null)) {
            throw new \UnexpectedValueException('Google returned an incomplete identity.');
        }

        return [
            'id' => $profile['sub'],
            'name' => $profile['name'],
            'email' => is_string($profile['email'] ?? null) ? $profile['email'] : null,
            'email_verified' => filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            'avatar' => is_string($profile['picture'] ?? null) ? $profile['picture'] : null,
            'metadata' => ['locale' => $profile['locale'] ?? null],
        ];
    }

    /** @return array{id: string, name: string, email: ?string, email_verified: bool, phone: ?string, avatar: ?string, metadata: array<string, mixed>} */
    private function telegramIdentity(string $code, array $flow, array $configuration): array
    {
        $tokens = Http::asForm()
            ->withBasicAuth($configuration['client_id'], $configuration['client_secret'])
            ->connectTimeout(3)
            ->timeout(10)
            ->retry([100, 300])
            ->post($configuration['token_url'], [
                'client_id' => $configuration['client_id'],
                'code' => $code,
                'code_verifier' => $flow['verifier'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $flow['redirect_uri'],
            ])
            ->throw()
            ->json();

        $claims = $this->tokenVerifier->verify(
            (string) ($tokens['id_token'] ?? ''),
            $configuration['jwks_url'],
            $configuration['issuer'],
            $configuration['client_id'],
            $flow['nonce'],
        );

        if (! is_string($claims['sub'] ?? null)) {
            throw new \UnexpectedValueException('Telegram returned an incomplete identity.');
        }

        $name = trim((string) ($claims['name'] ?? trim(($claims['given_name'] ?? '').' '.($claims['family_name'] ?? ''))));

        return [
            'id' => $claims['sub'],
            'name' => $name !== '' ? $name : 'Telegram customer',
            'email' => is_string($claims['email'] ?? null) ? $claims['email'] : null,
            'email_verified' => filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOL),
            'phone' => is_string($claims['phone_number'] ?? null) ? $claims['phone_number'] : null,
            'avatar' => is_string($claims['picture'] ?? null) ? $claims['picture'] : null,
            'metadata' => ['username' => $claims['preferred_username'] ?? null],
        ];
    }

    /** @return array<string, string> */
    private function configuration(string $provider): array
    {
        abort_unless(in_array($provider, ['google', 'telegram'], true), 404);

        return config("services.{$provider}", []);
    }

    /** @param array<string, string> $configuration */
    private function isAvailable(string $provider, array $configuration): bool
    {
        return filled($configuration['client_id'] ?? null)
            && filled($configuration['client_secret'] ?? null)
            && SocialAuthProvider::query()->where('provider', $provider)->where('is_enabled', true)->exists();
    }

    /** @param array<string, string> $configuration */
    private function redirectUri(string $provider, array $configuration): string
    {
        return filled($configuration['redirect'] ?? null)
            ? $configuration['redirect']
            : route('social.callback', $provider);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
