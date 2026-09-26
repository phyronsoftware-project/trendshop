<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Setting;
use App\Models\SocialLink;
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

            $view->with(compact('settings', 'socialLinks'));
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
