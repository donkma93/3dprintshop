<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductQrCode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductQrController extends Controller
{
    public function show(Product $product)
    {
        ProductQrCode::ensureImage($product);

        return view('admin.products.qr', [
            'product' => $product,
            'payload' => ProductQrCode::payload($product),
            'qrUrl' => ProductQrCode::imageUrl($product),
        ]);
    }

    /**
     * Tải tem: mặc định PNG gồm QR + mã SP + giá.
     * ?raw=1 → chỉ ảnh QR thuần.
     */
    public function download(Request $request, Product $product): StreamedResponse
    {
        $raw = $request->boolean('raw');
        if ($raw) {
            $path = ProductQrCode::ensureImage($product);
            $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
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

    public function regenerate(Request $request, Product $product)
    {
        try {
            $product->qr_image = ProductQrCode::generateAndStore($product);
            $product->save();
            // Làm mới tem nhãn
            ProductQrCode::storeLabel($product);
        } catch (\Throwable $e) {
            return back()->with('error', 'Không tạo lại được QR: '.$e->getMessage());
        }

        return back()->with('success', 'Đã tạo lại ảnh QR cho sản phẩm.');
    }
}
