<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('sku_prefix', 16)->nullable()->after('slug');
            $table->unique('sku_prefix');
        });

        // Gán prefix cho danh mục hiện có + SKU còn trống
        if (class_exists(Category::class) && Schema::hasTable('categories')) {
            Category::withTrashed()->orderBy('id')->each(function (Category $category) {
                if (empty($category->sku_prefix)) {
                    $category->sku_prefix = Category::uniqueSkuPrefix($category->name, $category->id);
                    $category->saveQuietly();
                }
            });

            Product::withTrashed()
                ->where(function ($q) {
                    $q->whereNull('sku')->orWhere('sku', '');
                })
                ->whereNotNull('category_id')
                ->orderBy('id')
                ->each(function (Product $product) {
                    $category = Category::withTrashed()->find($product->category_id);
                    if ($category) {
                        $product->sku = Product::generateUniqueSku($category, $product->id);
                        $product->saveQuietly();
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['sku_prefix']);
            $table->dropColumn('sku_prefix');
        });
    }
};
