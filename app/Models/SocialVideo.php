<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SocialVideo extends Model
{
    use HasFactory, SoftDeletes;

    public const PLATFORM_YOUTUBE = 'youtube';
    public const PLATFORM_TIKTOK = 'tiktok';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_OTHER = 'other';

    protected $fillable = [
        'title',
        'platform',
        'url',
        'external_id',
        'thumbnail',
        'preview_url',
        'channel_name',
        'sort_order',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function platformOptions(): array
    {
        return [
            self::PLATFORM_YOUTUBE => 'YouTube',
            self::PLATFORM_TIKTOK => 'TikTok',
            self::PLATFORM_FACEBOOK => 'Facebook',
            self::PLATFORM_OTHER => 'Khác',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForHome(Builder $query): Builder
    {
        return $query->active()
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }

    public function getPlatformLabelAttribute(): string
    {
        return self::platformOptions()[$this->platform] ?? $this->platform;
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail) {
            if (Str::startsWith($this->thumbnail, ['http://', 'https://', '//', 'data:'])) {
                return Str::startsWith($this->thumbnail, '//')
                    ? 'https:'.$this->thumbnail
                    : $this->thumbnail;
            }

            return asset('storage/'.$this->thumbnail);
        }

        if ($this->platform === self::PLATFORM_YOUTUBE && $this->external_id) {
            return 'https://i.ytimg.com/vi/'.$this->external_id.'/hqdefault.jpg';
        }

        // Soft brand poster — never empty black box
        $label = htmlspecialchars(Str::limit($this->title ?: ($this->platform_label), 28, ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $colors = match ($this->platform) {
            self::PLATFORM_YOUTUBE => ['#b91c1c', '#7f1d1d'],
            self::PLATFORM_TIKTOK => ['#0f172a', '#111827'],
            self::PLATFORM_FACEBOOK => ['#1d4ed8', '#1e3a8a'],
            default => ['#475569', '#1e293b'],
        };

        return 'data:image/svg+xml,'.rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0%" stop-color="'.$colors[0].'"/><stop offset="100%" stop-color="'.$colors[1].'"/>'
            .'</linearGradient></defs>'
            .'<rect width="640" height="360" fill="url(#g)"/>'
            .'<circle cx="320" cy="165" r="46" fill="rgba(255,255,255,.18)"/>'
            .'<polygon points="308,145 308,185 348,165" fill="#fff"/>'
            .'<text x="320" y="250" text-anchor="middle" fill="#fff" font-family="Arial,sans-serif" font-size="22" font-weight="700">'.$label.'</text>'
            .'</svg>'
        );
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->platform === self::PLATFORM_YOUTUBE && $this->external_id) {
            return 'https://www.youtube.com/embed/'.$this->external_id
                .'?autoplay=1&mute=1&controls=0&modestbranding=1&rel=0&playsinline=1';
        }

        return null;
    }

    public function getCanHoverPreviewAttribute(): bool
    {
        if ($this->preview_url) {
            return true;
        }

        return $this->platform === self::PLATFORM_YOUTUBE && (bool) $this->external_id;
    }

    /**
     * Parse URL → platform + external id + optional remote thumbnail (no HTTP).
     * Supports VOD + live stream URL shapes.
     *
     * @return array{platform:string,external_id:?string,thumbnail:?string}
     */
    public static function parseUrl(string $url): array
    {
        $url = trim($url);
        $platform = self::PLATFORM_OTHER;
        $externalId = null;
        $thumbnail = null;

        // YouTube video / shorts / embed / live by id
        // e.g. watch?v=, youtu.be/, shorts/, embed/, live/VIDEO_ID
        if (preg_match(
            '~(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i',
            $url,
            $m
        )) {
            $platform = self::PLATFORM_YOUTUBE;
            $externalId = $m[1];
            $thumbnail = 'https://i.ytimg.com/vi/'.$externalId.'/hqdefault.jpg';
        }
        // YouTube channel live pages: /@handle/live or /channel/UCxxx/live or /c/Name/live
        elseif (preg_match('~youtube\.com/(?:@[\w.-]+|channel/[\w-]+|c/[\w.-]+|user/[\w.-]+)/live(?:[/?#]|$)~i', $url)) {
            $platform = self::PLATFORM_YOUTUBE;
            // id resolved later via oEmbed / page fetch when stream is on
        }
        // TikTok video
        elseif (preg_match('~tiktok\.com/@([^/]+)/video/(\d+)~i', $url, $m)) {
            $platform = self::PLATFORM_TIKTOK;
            $externalId = $m[2];
        }
        // TikTok LIVE: /@user/live
        elseif (preg_match('~tiktok\.com/@([^/?#]+)/live(?:[/?#]|$)~i', $url, $m)) {
            $platform = self::PLATFORM_TIKTOK;
            $externalId = 'live:'.$m[1];
        }
        // TikTok short / profile links
        elseif (preg_match('~(?:vm\.|vt\.)?tiktok\.com/~i', $url)) {
            $platform = self::PLATFORM_TIKTOK;
        }
        // Facebook watch / video / live
        elseif (preg_match('~(?:facebook\.com|fb\.watch)/~i', $url)) {
            $platform = self::PLATFORM_FACEBOOK;
            if (preg_match('~/(?:videos|reel|watch)/(?:.+?/)?(\d{6,})~i', $url, $m)) {
                $externalId = $m[1];
            } elseif (preg_match('~[?&]v=(\d{6,})~i', $url, $m)) {
                $externalId = $m[1];
            }
        }

        return [
            'platform' => $platform,
            'external_id' => $externalId,
            'thumbnail' => $thumbnail,
        ];
    }
}
