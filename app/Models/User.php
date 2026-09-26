<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role', 'name', 'email', 'phone', 'password', 'profile_image_path', 'locale', 'status', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    public function chatConversation(): HasOne
    {
        return $this->hasOne(ChatConversation::class);
    }

    public function sentChatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /** Return the uploaded photo, verified social photo, or standard fallback. */
    public function avatarUrl(): string
    {
        if (filled($this->profile_image_path)) {
            return str_starts_with($this->profile_image_path, 'logo_web/')
                ? asset($this->profile_image_path)
                : asset('storage/'.$this->profile_image_path);
        }

        $providerAvatarUrl = $this->socialAccounts
            ->first(fn (UserSocialAccount $account): bool => is_string($account->provider_avatar_url)
                && str_starts_with($account->provider_avatar_url, 'https://'))
            ?->provider_avatar_url;

        return $providerAvatarUrl ?? asset('logo_web/me.jpg');
    }
}
