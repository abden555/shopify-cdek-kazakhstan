<?php

namespace App\Providers;

use App\Models\Setting;
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
        View::composer(['components.admin.sidebar', 'components.admin.header'], function ($view): void {
            $branding = Setting::query()
                ->whereNull('shop_id')
                ->where('setting_key', 'application_branding')
                ->value('value') ?? [];

            $view->with('applicationBranding', $branding);
        });
    }
}
