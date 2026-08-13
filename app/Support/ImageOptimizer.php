<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resize / nén ảnh upload cho web (GD).
 * Giữ tỉ lệ, giới hạn cạnh dài, xuất JPEG/WebP chất lượng phù hợp cửa hàng.
 */
class ImageOptimizer
{
    public const PRODUCT_MAX = 1200;

    public const OG_MAX = 1200;

    public const CATEGORY_MAX = 800;

    public const BANNER_MAX = 1920;

    public const DEFAULT_QUALITY = 82;

    /**
     * Lưu file ảnh đã tối ưu lên disk public.
     *
     * @return string đường dẫn relative trên disk (vd: products/abc.jpg)
     */
    public static function store(
        UploadedFile $file,
        string $directory,
        int $maxEdge = self::PRODUCT_MAX,
        int $quality = self::DEFAULT_QUALITY,
        string $disk = 'public'
    ): string {
        $binary = static::optimizeFile($file, $maxEdge, $quality);
        $ext = static::outputExtension($file);
        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.'.$ext;

        if (! Storage::disk($disk)->put($path, $binary)) {
            throw new RuntimeException('Không lưu được ảnh đã tối ưu.');
        }

        return $path;
    }

    /**
     * Đọc UploadedFile → binary ảnh đã resize.
     */
    public static function optimizeFile(UploadedFile $file, int $maxEdge = self::PRODUCT_MAX, int $quality = self::DEFAULT_QUALITY): string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('Không đọc được file ảnh upload.');
        }

        $mime = $file->getMimeType() ?: (mime_content_type($path) ?: '');

        return static::optimizePath($path, $mime, $maxEdge, $quality);
    }

    public static function optimizePath(string $path, ?string $mime = null, int $maxEdge = self::PRODUCT_MAX, int $quality = self::DEFAULT_QUALITY): string
    {
        if (! extension_loaded('gd')) {
            return (string) file_get_contents($path);
        }

        $mime = $mime ?: (mime_content_type($path) ?: '');
        $source = static::createImageResource($path, $mime);
        if (! $source) {
            // Fallback: lưu file gốc nếu không decode được
            return (string) file_get_contents($path);
        }

        $source = static::applyExifOrientation($source, $path, $mime);

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return (string) file_get_contents($path);
        }

        [$newW, $newH] = static::fitWithin($width, $height, $maxEdge);

        if ($newW === $width && $newH === $height) {
            $canvas = $source;
        } else {
            $canvas = imagecreatetruecolor($newW, $newH);
            if (! $canvas) {
                imagedestroy($source);

                return (string) file_get_contents($path);
            }

            // Nền trắng (tránh nền đen khi từ PNG/WebP có alpha)
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($source);
        }

        $binary = static::encode($canvas, $quality);
        imagedestroy($canvas);

        return $binary;
    }

    /**
     * @return array{0:int,1:int}
     */
    public static function fitWithin(int $width, int $height, int $maxEdge): array
    {
        $maxEdge = max(1, $maxEdge);
        $long = max($width, $height);
        if ($long <= $maxEdge) {
            return [$width, $height];
        }

        $scale = $maxEdge / $long;

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    private static function outputExtension(UploadedFile $file): string
    {
        // JPEG progressive: tương thích mọi trình duyệt / app mobile
        return 'jpg';
    }

    /**
     * @param  \GdImage|resource  $image
     */
    private static function encode($image, int $quality): string
    {
        $quality = max(40, min(95, $quality));
        ob_start();
        imageinterlace($image, true);
        imagejpeg($image, null, $quality);
        $binary = (string) ob_get_clean();
        if ($binary === '') {
            throw new RuntimeException('Encode ảnh thất bại.');
        }

        return $binary;
    }

    /**
     * @return \GdImage|resource|null
     */
    private static function createImageResource(string $path, string $mime)
    {
        $mime = strtolower($mime);

        try {
            return match (true) {
                str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
                str_contains($mime, 'png') => @imagecreatefrompng($path),
                str_contains($mime, 'gif') => @imagecreatefromgif($path),
                str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
                str_contains($mime, 'bmp') && function_exists('imagecreatefrombmp') => @imagecreatefrombmp($path),
                default => static::createFromAny($path),
            } ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return \GdImage|resource|null
     */
    private static function createFromAny(string $path)
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return @imagecreatefromstring($data) ?: null;
    }

    /**
     * Xoay ảnh theo EXIF (camera điện thoại hay bị lệch).
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    private static function applyExifOrientation($image, string $path, string $mime)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $mime = strtolower($mime);
        if (! str_contains($mime, 'jpeg') && ! str_contains($mime, 'jpg') && ! str_contains($mime, 'tiff')) {
            return $image;
        }

        try {
            $exif = @exif_read_data($path);
        } catch (\Throwable) {
            return $image;
        }

        $orientation = (int) ($exif['Orientation'] ?? 1);
        if ($orientation <= 1) {
            return $image;
        }

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
