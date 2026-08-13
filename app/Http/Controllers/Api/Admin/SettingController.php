<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends ApiController
{
    public function show(): JsonResponse
    {
        return $this->ok($this->formatSettings(SiteSetting::allCached()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'working_hours' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'zalo' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'footer_about' => ['nullable', 'string'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'home_about_title' => ['nullable', 'string', 'max:255'],
            'home_about_content' => ['nullable', 'string'],
            'home_why_title' => ['nullable', 'string', 'max:255'],
            'home_why_content' => ['nullable', 'string'],
            'google_analytics' => ['nullable', 'string', 'max:100'],
            'geo_region' => ['nullable', 'string', 'max:50'],
            'geo_placename' => ['nullable', 'string', 'max:255'],
            'geo_position' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
            'og_image' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach (['logo', 'favicon', 'og_image'] as $fileField) {
            if ($request->hasFile($fileField)) {
                $old = SiteSetting::getValue($fileField);
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $data[$fileField] = $request->file($fileField)->store('settings', 'public');
            } else {
                unset($data[$fileField]);
            }
        }

        SiteSetting::setMany($data);

        return $this->ok($this->formatSettings(SiteSetting::allCached()), 'Đã lưu cài đặt.');
    }

    private function formatSettings(array $settings): array
    {
        foreach (['logo', 'favicon', 'og_image'] as $fileField) {
            $path = $settings[$fileField] ?? null;
            $settings[$fileField.'_url'] = $path ? asset('storage/'.$path) : null;
        }

        return $settings;
    }
}
