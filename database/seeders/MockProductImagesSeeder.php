<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Gán ảnh mock sản phẩm in 3D thật (Wikimedia / stock complementary).
 * MakerWorld bị Cloudflare chặn scrape từ server — không tải được trực tiếp.
 */
class MockProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('products');

        $srcDir = storage_path('app/public/products');
        if (! is_dir($srcDir)) {
            $this->command?->error('Thiếu thư mục ảnh: '.$srcDir);

            return;
        }

        $cats = Category::query()->orderBy('id')->get()->keyBy(function ($c) {
            return match (true) {
                str_contains(mb_strtolower($c->name), 'trang trí') => 'decor',
                str_contains(mb_strtolower($c->name), 'phụ kiện') => 'accessories',
                str_contains(mb_strtolower($c->name), 'đồ chơi') => 'toys',
                str_contains(mb_strtolower($c->name), 'linh kiện') || str_contains(mb_strtolower($c->name), 'kỹ thuật') => 'tech',
                default => 'other_'.$c->id,
            };
        });

        $decor = $cats->get('decor')?->id ?? Category::query()->value('id');
        $acc = $cats->get('accessories')?->id ?? $decor;
        $toys = $cats->get('toys')?->id ?? $decor;
        $tech = $cats->get('tech')?->id ?? $decor;

        // Ảnh thật đã tải về storage/app/public/products
        $mapExisting = [
            'Tượng rồng mini' => 'products/mock_dragon.jpg',
            'Giá đỡ điện thoại hex' => 'products/mock_phone_stand.jpg',
            'Mô hình robot lắp ráp' => 'products/mock_robot_toy.jpg',
            'Bánh răng module 1' => 'products/mock_gear_triple.jpg',
            'Bình hoa hình học' => 'products/mock_vase1.jpg',
            'Móc khóa logo custom' => 'products/mock_key_metal.jpg',
        ];

        foreach ($mapExisting as $name => $rel) {
            if (! $disk->exists($rel)) {
                $this->command?->warn("Thiếu file: {$rel}");

                continue;
            }
            $product = Product::withTrashed()->where('name', $name)->first();
            if (! $product) {
                continue;
            }
            $product->image = $rel;
            $product->save();
            $this->command?->info("Updated image: {$name}");
        }

        $extras = [
            [
                'name' => 'Tượng mini phong cách cổ',
                'sku' => '3D-FIG-007',
                'category_id' => $decor,
                'image' => 'products/mock_figurine1.png',
                'short_description' => 'Tượng trang trí in 3D chi tiết, phong cách cổ điển.',
                'description' => "Mô hình tượng mini in 3D độ chi tiết cao.\nPhù hợp trưng bày kệ, bàn làm việc hoặc làm quà tặng.",
                'price' => 210000,
                'cost_price' => 70000,
                'stock' => 15,
                'material_used' => 'PLA Beige',
                'weight_grams' => 55,
                'is_featured' => true,
            ],
            [
                'name' => 'Bình hoa geometric cam',
                'sku' => '3D-VAS-008',
                'category_id' => $decor,
                'image' => 'products/mock_vase3.jpg',
                'short_description' => 'Bình hoa in 3D màu cam, form hiện đại.',
                'description' => 'Bình hoa trang trí in PLA/PETG. Chỉ dùng hoa giả hoặc cắm khô.',
                'price' => 275000,
                'cost_price' => 95000,
                'stock' => 9,
                'material_used' => 'PLA Cam',
                'weight_grams' => 110,
                'is_featured' => true,
            ],
            [
                'name' => 'Bình hoa xoắn 3D',
                'sku' => '3D-VAS-009',
                'category_id' => $decor,
                'image' => 'products/mock_vase2.jpg',
                'short_description' => 'Bình hoa form xoắn, in lớp mịn.',
                'description' => 'Thiết kế parametric xoắn ốc, nổi bật khi trưng bày.',
                'price' => 295000,
                'cost_price' => 100000,
                'stock' => 7,
                'material_used' => 'PLA Marble',
                'weight_grams' => 125,
                'is_featured' => false,
            ],
            [
                'name' => 'Bánh răng hành tinh helical',
                'sku' => '3D-GEAR-010',
                'category_id' => $tech,
                'image' => 'products/mock_gear_helical.jpg',
                'short_description' => 'Bộ bánh răng helical / planetary demo.',
                'description' => 'Mô hình cơ khí in PETG, dùng demo hoặc đồ án kỹ thuật.',
                'price' => 125000,
                'cost_price' => 40000,
                'stock' => 20,
                'material_used' => 'PETG',
                'weight_grams' => 48,
                'is_featured' => true,
            ],
            [
                'name' => 'Mô hình kỹ thuật cơ khí',
                'sku' => '3D-MEC-011',
                'category_id' => $tech,
                'image' => 'products/mock_mech_part.jpg',
                'short_description' => 'Chi tiết prototype cơ khí in 3D.',
                'description' => 'Phù hợp mockup linh kiện, kiểm tra lắp ráp trước khi gia công kim loại.',
                'price' => 99000,
                'cost_price' => 32000,
                'stock' => 18,
                'material_used' => 'PETG',
                'weight_grams' => 40,
                'is_featured' => false,
            ],
            [
                'name' => 'Khối hình học trang trí',
                'sku' => '3D-GEO-012',
                'category_id' => $decor,
                'image' => 'products/mock_geometry.jpg',
                'short_description' => 'Khối geometric decor, phong cách tối giản.',
                'description' => 'Set khối hình học in 3D trang trí nội thất / studio.',
                'price' => 165000,
                'cost_price' => 55000,
                'stock' => 14,
                'material_used' => 'PLA',
                'weight_grams' => 70,
                'is_featured' => true,
            ],
            [
                'name' => 'Mô hình miniature sưu tầm',
                'sku' => '3D-MIN-013',
                'category_id' => $toys,
                'image' => 'products/mock_miniature.jpg',
                'short_description' => 'Miniature in 3D tỉ lệ nhỏ, chi tiết cao.',
                'description' => 'Mô hình sưu tầm, có thể sơn và trưng bày.',
                'price' => 155000,
                'cost_price' => 50000,
                'stock' => 22,
                'material_used' => 'Resin / PLA',
                'weight_grams' => 30,
                'is_featured' => true,
            ],
            [
                'name' => 'Mô hình in FDM demo',
                'sku' => '3D-PRT-014',
                'category_id' => $toys,
                'image' => 'products/mock_print1.jpg',
                'short_description' => 'Mẫu demo chất lượng in FDM.',
                'description' => 'Sản phẩm showcase độ mịn layer, phù hợp showroom.',
                'price' => 120000,
                'cost_price' => 40000,
                'stock' => 10,
                'material_used' => 'PLA+',
                'weight_grams' => 60,
                'is_featured' => false,
            ],
            [
                'name' => 'Prototype linh kiện nhựa',
                'sku' => '3D-PRT-015',
                'category_id' => $tech,
                'image' => 'products/mock_print3.jpg',
                'short_description' => 'Prototype linh kiện nhựa in nhanh.',
                'description' => 'In nhanh để kiểm tra form-fit-function trước khi sản xuất hàng loạt.',
                'price' => 135000,
                'cost_price' => 45000,
                'stock' => 16,
                'material_used' => 'ABS / PETG',
                'weight_grams' => 55,
                'is_featured' => false,
            ],
            [
                'name' => 'Mô hình công nghệ showcase',
                'sku' => '3D-TEC-016',
                'category_id' => $acc,
                'image' => 'products/mock_tech1.jpg',
                'short_description' => 'Mô hình trang trí góc tech / desk setup.',
                'description' => 'Phụ kiện trang trí bàn làm việc phong cách công nghệ.',
                'price' => 145000,
                'cost_price' => 48000,
                'stock' => 11,
                'material_used' => 'PLA',
                'weight_grams' => 42,
                'is_featured' => true,
            ],
            [
                'name' => 'Chi tiết lắp ráp demo',
                'sku' => '3D-TEC-017',
                'category_id' => $acc,
                'image' => 'products/mock_tech2.jpg',
                'short_description' => 'Chi tiết lắp ráp in 3D cho desk setup.',
                'description' => 'Sản phẩm phụ kiện nhỏ, hoàn thiện mịn, màu trung tính.',
                'price' => 79000,
                'cost_price' => 25000,
                'stock' => 25,
                'material_used' => 'PLA',
                'weight_grams' => 28,
                'is_featured' => false,
            ],
            [
                'name' => 'Mẫu in studio 3D',
                'sku' => '3D-PRT-018',
                'category_id' => $toys,
                'image' => 'products/mock_print4.jpg',
                'short_description' => 'Mẫu trưng bày studio in 3D.',
                'description' => 'Mẫu showcase dùng cho catalog và demo khách hàng.',
                'price' => 110000,
                'cost_price' => 36000,
                'stock' => 13,
                'material_used' => 'PLA',
                'weight_grams' => 50,
                'is_featured' => false,
            ],
        ];

        foreach ($extras as $item) {
            if (! $disk->exists($item['image'])) {
                $this->command?->warn('Bỏ qua (thiếu ảnh): '.$item['name']);

                continue;
            }

            $existing = Product::withTrashed()->where('sku', $item['sku'])->first();
            if ($existing) {
                $existing->fill(array_merge($item, ['is_active' => true, 'deleted_at' => null]));
                $existing->save();
                $this->command?->info('Updated product: '.$item['name']);
            } else {
                Product::create(array_merge($item, [
                    'is_active' => true,
                    'sort_order' => 0,
                ]));
                $this->command?->info('Created product: '.$item['name']);
            }
        }

        // Dọn file rác rate-limit nếu còn
        foreach (File::files($srcDir) as $file) {
            if ($file->getSize() < 5000) {
                File::delete($file->getPathname());
            }
        }

        $this->command?->info('Done. Products with images: '.Product::whereNotNull('image')->where('image', '!=', '')->count());
    }
}
