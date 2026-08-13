<?php

use App\Models\Product;
use App\Support\ProductQrCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('qr_token', 40)->nullable()->unique()->after('sku');
            $table->string('qr_image', 255)->nullable()->after('qr_token');
        });

        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sale_code', 40)->unique();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->unsignedInteger('stock_before')->default(0);
            $table->unsignedInteger('stock_after')->default(0);
            $table->string('scan_payload', 255)->nullable();
            $table->string('channel', 30)->default('qr_internal');
            $table->string('note', 500)->nullable();
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamps();

            $table->index(['sold_at']);
            $table->index(['product_id', 'sold_at']);
        });

        // Backfill QR cho sản phẩm hiện có
        Product::withTrashed()->orderBy('id')->chunkById(50, function ($products) {
            foreach ($products as $product) {
                if ($product->qr_token) {
                    continue;
                }
                $product->qr_token = ProductQrCode::uniqueToken();
                $product->saveQuietly();
                try {
                    $product->qr_image = ProductQrCode::generateAndStore($product);
                    $product->saveQuietly();
                } catch (\Throwable) {
                    // GD/disk lỗi — token vẫn dùng được, QR sinh lại khi mở trang
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_image']);
        });
    }
};
