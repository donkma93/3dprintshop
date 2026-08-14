<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $settings = SiteSetting::allCached();
        $page = Page::published()->where('slug', $slug)->firstOrFail();
        $siteName = $settings['site_name'] ?? 'Shop3DPrinting';

        $seo = [
            'title' => $page->seo_title.($page->meta_title ? '' : ' | '.$siteName),
            'description' => $page->seo_description,
            'keywords' => $page->meta_keywords,
            'canonical' => route('shop.pages.show', $page->slug),
            'og_type' => 'website',
            'og_image' => $page->og_image_url,
        ];

        return view('shop.pages.show', compact('page', 'seo', 'settings'));
    }
}
