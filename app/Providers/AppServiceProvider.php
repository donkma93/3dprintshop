<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['layouts.shop', 'shop.*'], function ($view) {
            $settings = [];
            $menuPages = collect();
            $menuCategories = collect();

            try {
                if (Schema::hasTable('site_settings')) {
                    $settings = SiteSetting::allCached();
                }
                if (Schema::hasTable('pages')) {
                    $menuPages = Page::published()
                        ->where('show_in_menu', true)
                        ->orderBy('sort_order')
                        ->get(['id', 'title', 'slug']);
                }
                if (Schema::hasTable('categories')) {
                    $menuCategories = Category::where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get(['id', 'name', 'slug']);
                }
            } catch (\Throwable $e) {
                // DB not ready during install
            }

            $view->with(compact('settings', 'menuPages', 'menuCategories'));
        });
    }
}
