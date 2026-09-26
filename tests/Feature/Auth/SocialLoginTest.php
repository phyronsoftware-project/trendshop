<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAuthProvider;
use App\Models\User;
use App\Models\UserSocialAccount;
use App\Services\OidcTokenVerifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('social_auth_providers') || ! Schema::hasTable('user_social_accounts')) {
            $this->markTestSkipped('TrendShop social authentication schema is required.');
        }

        Config::set('services.google.client_id', 'google-client');
        Config::set('services.google.client_secret', 'google-secret');
        Config::set('services.google.redirect', 'http://localhost/auth/google/callback');
        Config::set('services.telegram.client_id', 'telegram-client');
        Config::set('services.telegram.client_secret', 'telegram-secret');
        Config::set('services.telegram.redirect', 'http://localhost/auth/telegram/callback');

        SocialAuthProvider::query()->whereIn('provider', ['google', 'telegram'])->update(['is_enabled' => true]);
    }

    public function test_google_login_uses_pkce_and_links_a_verified_identity(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'name' => 'Google Customer',
                'email' => 'google-customer@example.test',
                'email_verified' => true,
                'picture' => 'https://example.test/avatar.jpg',
            ]),
        ]);

        $authorization = $this->get(route('social.redirect', 'google'));
        $flow = session('social_auth.google');

        $authorization->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
        $this->assertNotEmpty($flow['verifier']);
        $this->assertNotEmpty($flow['state']);

        $callback = $this->get(route('social.callback', [
            'provider' => 'google',
            'code' => 'authorization-code',
            'state' => $flow['state'],
        ]));

        $user = User::query()->where('email', 'google-customer@example.test')->firstOrFail();
        $callback->assertRedirect(route('profile'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas(UserSocialAccount::class, [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-123',
        ]);
        $this->get(route('profile'))
            ->assertSee('https://example.test/avatar.jpg', false);
    }

    public function test_telegram_login_verifies_the_id_token_before_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth.telegram.org/token' => Http::response(['id_token' => 'signed-telegram-token']),
        ]);

        $authorization = $this->get(route('social.redirect', 'telegram'));
        $flow = session('social_auth.telegram');

        $authorization->assertRedirectContains('https://oauth.telegram.org/auth');
        $this->mock(OidcTokenVerifier::class, function (MockInterface $mock) use ($flow): void {
            $mock->shouldReceive('verify')
                ->once()
                ->with(
                    'signed-telegram-token',
                    'https://oauth.telegram.org/.well-known/jwks.json',
                    'https://oauth.telegram.org',
                    'telegram-client',
                    $flow['nonce'],
                )
                ->andReturn([
                    'sub' => 'telegram-user-456',
                    'name' => 'Telegram Customer',
                    'preferred_username' => 'telegram_customer',
                    'picture' => 'https://example.test/telegram-avatar.jpg',
                ]);
        });

        $callback = $this->get(route('social.callback', [
            'provider' => 'telegram',
            'code' => 'authorization-code',
            'state' => $flow['state'],
        ]));

        $callback->assertRedirect(route('profile'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas(UserSocialAccount::class, [
            'provider' => 'telegram',
            'provider_user_id' => 'telegram-user-456',
        ]);
    }

    public function test_social_callback_rejects_an_invalid_state_without_contacting_the_provider(): void
    {
        Http::preventStrayRequests();
        $this->get(route('social.redirect', 'google'));

        $response = $this->get(route('social.callback', [
            'provider' => 'google',
            'code' => 'authorization-code',
            'state' => 'invalid-state',
        ]));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        Http::assertNothingSent();
    }
}
