<?php

namespace App\Support;

use App\Models\Product;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mã QR nội bộ cho bán hàng offline — không trỏ URL website.
 * Payload: QLBH|v1|{token}
 */
class ProductQrCode
{
    public const PREFIX = 'QLBH';

    public const VERSION = 'v1';

    public static function uniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(12));
        } while (Product::withTrashed()->where('qr_token', $token)->exists());

        return $token;
    }

    public static function payload(Product $product): string
    {
        $token = $product->qr_token ?: static::uniqueToken();

        return self::PREFIX.'|'.self::VERSION.'|'.$token;
    }

    public static function extractToken(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // QLBH|v1|TOKEN
        if (preg_match('/^QLBH\|v\d+\|([A-Z0-9\-]+)$/i', $raw, $m)) {
            return strtoupper($m[1]);
        }

        // Chỉ token
        if (preg_match('/^[A-Z0-9\-]{8,40}$/i', $raw)) {
            return strtoupper($raw);
        }

        // URL nội bộ nếu lỡ encode: ...?token=XXX hoặc /scan/XXX
        if (preg_match('/[?&]token=([A-Z0-9\-]+)/i', $raw, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('#/(?:scan|qr)/([A-Z0-9\-]+)#i', $raw, $m)) {
            return strtoupper($m[1]);
        }

        // SKU dạng PREFIX-0001
        if (preg_match('/^[A-Z0-9]{2,8}-\d{3,6}$/i', $raw)) {
            return strtoupper($raw);
        }

        return $raw !== '' ? $raw : null;
    }

    public static function ensureToken(Product $product): string
    {
        if (empty($product->qr_token)) {
            $product->qr_token = static::uniqueToken();
            if ($product->exists) {
                $product->saveQuietly();
            }
        }

        return $product->qr_token;
    }

    /**
     * Sinh PNG QR và lưu public disk. Trả về path relative.
     */
    public static function generateAndStore(Product $product, int $size = 360): string
    {
        static::ensureToken($product);
        $payload = static::payload($product);

        $result = Builder::create()
            ->writer(new PngWriter)
            ->writerOptions([])
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size($size)
            ->margin(12)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        $path = 'products/qr/'.$product->qr_token.'.png';
        Storage::disk('public')->put($path, $result->getString());

        return $path;
    }

    public static function ensureImage(Product $product): string
    {
        static::ensureToken($product);

        if ($product->qr_image && Storage::disk('public')->exists($product->qr_image)) {
            return $product->qr_image;
        }

        $path = static::generateAndStore($product);
        $product->qr_image = $path;
        if ($product->exists) {
            $product->saveQuietly();
        }

        return $path;
    }

    public static function imageUrl(Product $product): string
    {
        $path = static::ensureImage($product);

        return asset('storage/'.$path);
    }

    /**
     * Tem in: QR + mã SP (SKU) + giá — trả binary PNG.
     */
    public static function labelPngBinary(Product $product, int $qrSize = 280): string
    {
        static::ensureToken($product);
        $qrPath = static::ensureImage($product);
        $qrFull = Storage::disk('public')->path($qrPath);

        $qr = @imagecreatefrompng($qrFull);
        if (! $qr) {
            // Fallback: sinh lại từ payload
            $payload = static::payload($product);
            $result = Builder::create()
                ->writer(new PngWriter)
                ->data($payload)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->size($qrSize)
                ->margin(8)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->build();
            $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'qr_'.uniqid().'.png';
            file_put_contents($tmp, $result->getString());
            $qr = imagecreatefrompng($tmp);
            @unlink($tmp);
        }

        if (! $qr) {
            throw new \RuntimeException('Không tạo được ảnh QR cho tem.');
        }

        $qrW = imagesx($qr);
        $qrH = imagesy($qr);

        // Scale QR về kích thước chuẩn
        if ($qrW !== $qrSize) {
            $scaled = imagecreatetruecolor($qrSize, $qrSize);
            $white = imagecolorallocate($scaled, 255, 255, 255);
            imagefill($scaled, 0, 0, $white);
            imagecopyresampled($scaled, $qr, 0, 0, 0, 0, $qrSize, $qrSize, $qrW, $qrH);
            imagedestroy($qr);
            $qr = $scaled;
            $qrW = $qrH = $qrSize;
        }

        $pad = 24;
        $textBlock = 72;
        $canvasW = $qrW + $pad * 2;
        $canvasH = $qrH + $pad + $textBlock + $pad;
        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        $bg = imagecolorallocate($canvas, 255, 255, 255);
        $fg = imagecolorallocate($canvas, 15, 23, 42);
        $muted = imagecolorallocate($canvas, 71, 85, 105);
        imagefill($canvas, 0, 0, $bg);
        imagecopy($canvas, $qr, $pad, $pad, 0, 0, $qrW, $qrH);
        imagedestroy($qr);

        $sku = $product->sku ?: $product->qr_token;
        $price = number_format((float) $product->final_price, 0, ',', '.').' đ';

        // Built-in font (không phụ thuộc TTF) — căn giữa
        $skuFont = 5;
        $priceFont = 5;
        $skuW = imagefontwidth($skuFont) * strlen($sku);
        $priceW = imagefontwidth($priceFont) * strlen($price);
        $ySku = $pad + $qrH + 12;
        $yPrice = $ySku + 22;
        imagestring($canvas, $skuFont, (int) max(0, ($canvasW - $skuW) / 2), $ySku, $sku, $fg);
        imagestring($canvas, $priceFont, (int) max(0, ($canvasW - $priceW) / 2), $yPrice, $price, $muted);

        ob_start();
        imagepng($canvas);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }

    public static function storeLabel(Product $product): string
    {
        $binary = static::labelPngBinary($product);
        $path = 'products/qr/label-'.($product->qr_token ?: $product->id).'.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function labelUrl(Product $product): string
    {
        $path = static::storeLabel($product);

        return asset('storage/'.$path);
    }

    /**
     * Dữ liệu tem chuẩn cho web + API.
     *
     * @return array<string, mixed>
     */
    public static function labelData(Product $product): array
    {
        static::ensureImage($product);

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->price,
            'final_price' => (float) $product->final_price,
            'price_formatted' => number_format((float) $product->final_price, 0, ',', '.').' đ',
            'is_on_sale' => (bool) $product->is_on_sale,
            'qr_token' => $product->qr_token,
            'qr_payload' => static::payload($product),
            'qr_image_url' => static::imageUrl($product),
            'label_image_url' => static::labelUrl($product),
            'print' => [
                'sku' => $product->sku ?: $product->qr_token,
                'price' => number_format((float) $product->final_price, 0, ',', '.').' đ',
            ],
        ];
    }
}
