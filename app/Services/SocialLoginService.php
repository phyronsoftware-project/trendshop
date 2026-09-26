<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialLoginService
{
    /**
     * Resolve a verified provider identity to an active TrendShop account.
     *
     * @param  array{id: string, name: string, email?: ?string, email_verified?: bool, phone?: ?string, avatar?: ?string, metadata?: array<string, mixed>}  $identity
     */
    public function resolve(string $provider, array $identity, string $locale): User
    {
        return DB::transaction(function () use ($provider, $identity, $locale): User {
            $socialAccount = UserSocialAccount::query()
                ->with('user')
                ->where('provider', $provider)
                ->where('provider_user_id', $identity['id'])
                ->lockForUpdate()
                ->first();

            $user = $socialAccount?->user;
            $verifiedEmail = ($identity['email_verified'] ?? false) ? ($identity['email'] ?? null) : null;

            if ($user === null && $verifiedEmail !== null) {
                $user = User::query()->where('email', $verifiedEmail)->lockForUpdate()->first();
            }

            if ($user === null) {
                $user = User::query()->create([
                    'role' => 'customer',
                    'name' => $identity['name'],
                    'email' => $verifiedEmail,
                    'phone' => $identity['phone'] ?? null,
                    'password' => null,
                    'locale' => in_array($locale, ['km', 'en', 'zh'], true) ? $locale : 'km',
                    'status' => 'active',
                ]);

                if ($verifiedEmail !== null) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }
            }

            if ($user->role !== 'customer') {
                throw ValidationException::withMessages(['email' => 'Administrator accounts must use the administrator login page.']);
            }

            $existingProviderAccount = $user->socialAccounts()
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if ($existingProviderAccount !== null && $existingProviderAccount->provider_user_id !== $identity['id']) {
                throw ValidationException::withMessages(['email' => 'This account is already linked to another '.$provider.' identity.']);
            }

            $socialAccount ??= $existingProviderAccount ?? $user->socialAccounts()->make(['provider' => $provider]);
            $socialAccount->fill([
                'provider_user_id' => $identity['id'],
                'provider_email' => $identity['email'] ?? null,
                'provider_avatar_url' => $identity['avatar'] ?? null,
                'metadata' => $identity['metadata'] ?? [],
            ])->save();

            if ($user->status !== 'active') {
                throw ValidationException::withMessages(['email' => 'This account is not active. Please contact support.']);
            }

            $user->update(['last_login_at' => now()]);

            return $user;
        });
    }
}
