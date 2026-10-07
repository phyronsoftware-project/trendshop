<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\CartItem;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in Production
        |--------------------------------------------------------------------------
        |
        | Vercel terminates HTTPS before forwarding the request to Laravel.
        | Force Laravel's generated URLs, assets, routes, and forms to use
        | the public HTTPS production URL.
        |
        */
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        /*
        |--------------------------------------------------------------------------
        | Public Settings
        |--------------------------------------------------------------------------
        */
        View::composer(['components.header', 'components.footer'], function ($view): void {
            $settings = Schema::hasTable('settings')
                ? Setting::query()
                    ->where('is_public', true)
                    ->pluck('value', 'setting_key')
                : collect();

            $socialLinks = Schema::hasTable('social_links')
                ? SocialLink::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                : collect();

            // Share compact shopping counts with the authenticated storefront header.
            $wishlistCount = 0;
            $cartCount = 0;
            if (auth()->check()) {
                $wishlistCount = Schema::hasTable('wishlist_items')
                    ? WishlistItem::query()->where('user_id', auth()->id())->count()
                    : 0;
                $cartCount = Schema::hasTable('cart_items')
                    ? (int) CartItem::query()->whereHas('cart', fn ($query) => $query
                        ->where('user_id', auth()->id())
                        ->where('status', 'active'))->sum('quantity')
                    : 0;
            }

            $view->with(compact('cartCount', 'settings', 'socialLinks', 'wishlistCount'));
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Notifications
        |--------------------------------------------------------------------------
        */
        View::composer('admin.*', function ($view): void {
            $adminUnreadNotifications = Schema::hasTable('admin_notifications')
                ? AdminNotification::query()
                    ->whereNull('read_at')
                    ->count()
                : 0;

            $view->with(compact('adminUnreadNotifications'));
        });
    }
}
