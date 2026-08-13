<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Product;
use App\Support\ProductQrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API tem QR sản phẩm — parity với admin web.
 */
class ProductQrController extends ApiController
{
    /**
     * GET /products/{product}/qr
     * Metadata tem: QR, SKU, giá, URL ảnh.
     */
    public function show(Product $product): JsonResponse
    {
        return $this->ok(ProductQrCode::labelData($product));
    }

    /**
     * GET /products/{product}/qr/download
     * PNG tem (QR + mã + giá). ?raw=1 = chỉ QR.
     */
    public function download(Request $request, Product $product): StreamedResponse
    {
        $raw = $request->boolean('raw');
        if ($raw) {
            $path = ProductQrCode::ensureImage($product);
            $binary = Storage::disk('public')->get($path);
            $filename = 'QR-'.($product->sku ?: $product->qr_token).'.png';
        } else {
            $binary = ProductQrCode::labelPngBinary($product);
            $filename = 'TEM-'.($product->sku ?: $product->qr_token).'.png';
        }

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * POST /products/{product}/qr/regenerate
     * Tạo lại PNG QR (giữ token).
     */
    public function regenerate(Product $product): JsonResponse
    {
        try {
            $product->qr_image = ProductQrCode::generateAndStore($product);
            $product->save();
            ProductQrCode::storeLabel($product);
        } catch (\Throwable $e) {
            return $this->fail('Không tạo lại được QR: '.$e->getMessage(), 500);
        }

        return $this->ok(ProductQrCode::labelData($product->fresh()), 'Đã tạo lại ảnh QR.');
    }
}
