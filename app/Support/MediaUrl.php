<?php

namespace App\Support;

/**
 * URL media theo request hiện tại (không kẹt APP_URL=http://localhost).
 */
class MediaUrl
{
    public static function fromStorage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return static::rewriteLocalhost($path);
        }

        // url() dùng root của request HTTP hiện tại khi có request.
        return url('storage/'.ltrim($path, '/'));
    }

    public static function rewriteLocalhost(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $host = strtolower($parts['host']);
        if (! in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
            return $url;
        }

        if (! app()->runningInConsole() && request()) {
            $path = $parts['path'] ?? '/';
            $query = isset($parts['query']) ? '?'.$parts['query'] : '';

            return rtrim(request()->getSchemeAndHttpHost(), '/').$path.$query;
        }

        return $url;
    }
}
