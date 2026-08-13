<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class SettingController extends ApiController
{
    public function show(): JsonResponse
    {
        $settings = SiteSetting::allCached();

        $keys = [
            'site_name', 'site_tagline', 'phone', 'hotline', 'email', 'address',
            'working_hours', 'facebook', 'zalo', 'youtube', 'footer_about',
            'footer_copyright', 'home_about_title', 'home_about_content',
            'home_why_title', 'home_why_content',
        ];

        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $settings[$key] ?? null;
        }

        foreach (['logo', 'favicon', 'og_image'] as $fileField) {
            $path = $settings[$fileField] ?? null;
            $out[$fileField] = $path;
            $out[$fileField.'_url'] = $path ? asset('storage/'.$path) : null;
        }

        return $this->ok($out);
    }
}
