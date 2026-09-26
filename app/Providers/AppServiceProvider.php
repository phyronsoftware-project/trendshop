<?php

namespace App\Providers;

use App\Models\AdminNotification;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Schema;
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
        // Share administrator-managed public settings without changing component structure.
        View::composer(['components.header', 'components.footer'], function ($view): void {
            $settings = Schema::hasTable('settings') ? Setting::query()->where('is_public', true)->pluck('value', 'setting_key') : collect();
            $socialLinks = Schema::hasTable('social_links') ? SocialLink::query()->where('is_active', true)->orderBy('sort_order')->get() : collect();
            $view->with(compact('settings', 'socialLinks'));
        });

        View::composer('admin.*', function ($view): void {
            $adminUnreadNotifications = Schema::hasTable('admin_notifications') ? AdminNotification::query()->whereNull('read_at')->count() : 0;
            $view->with(compact('adminUnreadNotifications'));
        });
    }
}
