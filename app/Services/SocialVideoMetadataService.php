<?php

namespace App\Services;

use App\Models\SocialVideo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Resolve online social video/live links → platform, id, thumbnail, title, channel.
 * Thumbnails always come from the network (oEmbed / og:image / YouTube image CDN).
 */
class SocialVideoMetadataService
{
    /**
     * @return array{
     *     platform:string,
     *     external_id:?string,
     *     thumbnail:?string,
     *     title:?string,
     *     channel_name:?string,
     *     url:string
     * }
     */
    public function resolve(string $url): array
    {
        $url = trim($url);
        $parsed = SocialVideo::parseUrl($url);

        $platform = $parsed['platform'];
        $externalId = $parsed['external_id'];
        $thumbnail = $parsed['thumbnail'];
        $title = null;
        $channelName = null;
        $canonicalUrl = $url;

        // Follow short TikTok links so oEmbed / parse get a full URL
        if ($platform === SocialVideo::PLATFORM_TIKTOK && $this->isShortTikTokUrl($url)) {
            $expanded = $this->expandRedirect($url);
            if ($expanded) {
                $canonicalUrl = $expanded;
                $reparsed = SocialVideo::parseUrl($expanded);
                $platform = $reparsed['platform'] ?: $platform;
                $externalId = $reparsed['external_id'] ?: $externalId;
                $thumbnail = $reparsed['thumbnail'] ?: $thumbnail;
            }
        }

        // oEmbed (YouTube, TikTok, some Facebook)
        $oembed = $this->fetchOEmbed($canonicalUrl, $platform);
        if ($oembed) {
            $title = $oembed['title'] ?? $title;
            $channelName = $oembed['author_name'] ?? $channelName;
            if (! empty($oembed['thumbnail_url'])) {
                $thumbnail = $oembed['thumbnail_url'];
            }
            if (! empty($oembed['thumbnail_width']) && empty($thumbnail) && ! empty($oembed['thumbnail_url'])) {
                $thumbnail = $oembed['thumbnail_url'];
            }
        }

        // YouTube: always prefer CDN image when we have an id
        if ($platform === SocialVideo::PLATFORM_YOUTUBE && $externalId) {
            $thumbnail = $this->youtubeThumbnail($externalId, $thumbnail);
        }

        // Fallback: scrape og:image from page HTML
        if (! $thumbnail) {
            $og = $this->fetchOpenGraph($canonicalUrl);
            if ($og) {
                $thumbnail = $og['image'] ?? $thumbnail;
                $title = $title ?: ($og['title'] ?? null);
            }
        }

        // Do not persist data-URI posters in DB — model accessor renders them when null.
        // Only keep real http(s) thumbnail URLs (truncate extreme edge cases).
        if ($thumbnail && ! $this->isPersistableThumbnail($thumbnail)) {
            $thumbnail = null;
        } elseif (is_string($thumbnail) && strlen($thumbnail) > 2000) {
            $thumbnail = Str::limit($thumbnail, 2000, '');
        }

        return [
            'platform' => $platform,
            'external_id' => $externalId,
            'thumbnail' => $thumbnail,
            'title' => $title ? Str::limit(trim(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8')), 255, '') : null,
            'channel_name' => $channelName ? Str::limit(trim($channelName), 120, '') : null,
            'url' => $canonicalUrl,
        ];
    }

    private function isPersistableThumbnail(string $thumbnail): bool
    {
        return Str::startsWith($thumbnail, ['http://', 'https://', '//']);
    }

    private function isShortTikTokUrl(string $url): bool
    {
        return (bool) preg_match('~https?://(?:vm|vt)\.tiktok\.com/~i', $url);
    }

    private function expandRedirect(string $url): ?string
    {
        try {
            $response = Http::timeout(8)
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->get($url);

            $history = $response->handlerStats()['redirect_url'] ?? null;
            $effective = (string) $response->effectiveUri();
            if ($effective && $effective !== $url) {
                return $effective;
            }
            if (is_string($history) && $history !== '') {
                return $history;
            }
        } catch (\Throwable $e) {
            Log::debug('SocialVideo expandRedirect failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchOEmbed(string $url, string $platform): ?array
    {
        $endpoints = match ($platform) {
            SocialVideo::PLATFORM_YOUTUBE => [
                'https://www.youtube.com/oembed?format=json&url='.rawurlencode($url),
            ],
            SocialVideo::PLATFORM_TIKTOK => [
                'https://www.tiktok.com/oembed?url='.rawurlencode($url),
            ],
            SocialVideo::PLATFORM_FACEBOOK => [
                'https://www.facebook.com/plugins/video/oembed.json?url='.rawurlencode($url),
                'https://noembed.com/embed?url='.rawurlencode($url),
            ],
            default => [
                'https://noembed.com/embed?url='.rawurlencode($url),
            ],
        };

        // noembed as universal backup
        if ($platform !== SocialVideo::PLATFORM_FACEBOOK) {
            $endpoints[] = 'https://noembed.com/embed?url='.rawurlencode($url);
        }

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => $this->userAgent(), 'Accept' => 'application/json'])
                    ->get($endpoint);

                if (! $response->successful()) {
                    continue;
                }

                $data = $response->json();
                if (! is_array($data) || isset($data['error'])) {
                    continue;
                }

                return $data;
            } catch (\Throwable $e) {
                Log::debug('SocialVideo oEmbed failed', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * @return array{image?:string,title?:string}|null
     */
    private function fetchOpenGraph(string $url): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => $this->userAgent(), 'Accept' => 'text/html'])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            if ($html === '') {
                return null;
            }

            $image = $this->metaContent($html, 'og:image')
                ?: $this->metaContent($html, 'twitter:image')
                ?: $this->metaContent($html, 'twitter:image:src');

            $title = $this->metaContent($html, 'og:title')
                ?: $this->metaContent($html, 'twitter:title');

            if (! $image && ! $title) {
                return null;
            }

            return array_filter([
                'image' => $image ? $this->absolutizeUrl($image, $url) : null,
                'title' => $title,
            ]);
        } catch (\Throwable $e) {
            Log::debug('SocialVideo og:image failed', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return null;
    }

    private function metaContent(string $html, string $property): ?string
    {
        $patterns = [
            '/<meta[^>]+property=["\']'.preg_quote($property, '/').'["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']'.preg_quote($property, '/').'["\']/i',
            '/<meta[^>]+name=["\']'.preg_quote($property, '/').'["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']'.preg_quote($property, '/').'["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }

    private function absolutizeUrl(string $image, string $baseUrl): string
    {
        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return Str::startsWith($image, '//') ? 'https:'.$image : $image;
        }

        $parts = parse_url($baseUrl);
        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $image;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];
        if (Str::startsWith($image, '/')) {
            return $origin.$image;
        }

        return $origin.'/'.$image;
    }

    private function youtubeThumbnail(string $videoId, ?string $current): string
    {
        // maxres may 404 for some lives/old videos; hqdefault is reliable
        $hq = 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg';
        $mq = 'https://i.ytimg.com/vi/'.$videoId.'/mqdefault.jpg';
        $max = 'https://i.ytimg.com/vi/'.$videoId.'/maxresdefault.jpg';

        // Prefer existing remote YT thumb if already set
        if ($current && str_contains($current, 'ytimg.com') && str_contains($current, $videoId)) {
            return $current;
        }

        // Probe maxres quickly; fall back to hq
        try {
            $head = Http::timeout(4)->withHeaders(['User-Agent' => $this->userAgent()])->head($max);
            if ($head->successful() && (int) $head->header('Content-Length') > 2000) {
                return $max;
            }
        } catch (\Throwable) {
            // ignore
        }

        return $hq ?: $mq;
    }

    private function userAgent(): string
    {
        return 'Mozilla/5.0 (compatible; Shop3DPrintingBot/1.0; +https://shop3dprinting.local)';
    }
}
