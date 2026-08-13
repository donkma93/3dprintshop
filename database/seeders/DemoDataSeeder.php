<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Mô hình trang trí', 'description' => 'Tượng, mô hình trang trí bàn làm việc'],
            ['name' => 'Phụ kiện', 'description' => 'Giá đỡ, móc khóa, case điện thoại'],
            ['name' => 'Đồ chơi', 'description' => 'Mô hình lắp ráp, đồ chơi in 3D'],
            ['name' => 'Linh kiện kỹ thuật', 'description' => 'Bánh răng, khung, chi tiết máy'],
        ])->map(fn ($item) => Category::create(array_merge($item, ['is_active' => true])));

        $materials = [
            Material::create([
                'name' => 'PLA Trắng 1kg',
                'type' => 'PLA',
                'color' => 'Trắng',
                'brand' => 'eSUN',
                'unit' => 'kg',
                'stock_quantity' => 0,
                'unit_price' => 180000,
                'min_stock' => 1,
                'is_active' => true,
            ]),
            Material::create([
                'name' => 'PLA Đen 1kg',
                'type' => 'PLA',
                'color' => 'Đen',
                'brand' => 'Polymaker',
                'unit' => 'kg',
                'stock_quantity' => 0,
                'unit_price' => 200000,
                'min_stock' => 1,
                'is_active' => true,
            ]),
            Material::create([
                'name' => 'PETG Trong 1kg',
                'type' => 'PETG',
                'color' => 'Trong suốt',
                'brand' => 'eSUN',
                'unit' => 'kg',
                'stock_quantity' => 0,
                'unit_price' => 250000,
                'min_stock' => 0.5,
                'is_active' => true,
            ]),
            Material::create([
                'name' => 'Resin Standard Xám',
                'type' => 'Resin',
                'color' => 'Xám',
                'brand' => 'Anycubic',
                'unit' => 'lít',
                'stock_quantity' => 0,
                'unit_price' => 320000,
                'min_stock' => 0.5,
                'is_active' => true,
            ]),
        ];

        $inputs = [
            [$materials[0], 5, 175000, 'ShopFilament VN', '2026-07-01'],
            [$materials[1], 3, 195000, '3DMarket', '2026-07-10'],
            [$materials[2], 2, 245000, 'ShopFilament VN', '2026-07-20'],
            [$materials[3], 1.5, 310000, 'Anycubic Store', '2026-08-01'],
        ];

        foreach ($inputs as [$material, $qty, $price, $supplier, $date]) {
            MaterialInput::create([
                'material_id' => $material->id,
                'input_date' => $date,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_price' => $qty * $price,
                'supplier' => $supplier,
                'invoice_number' => 'PN-'.strtoupper(substr(md5($material->name.$date), 0, 6)),
            ]);

            $material->update([
                'stock_quantity' => $qty,
                'unit_price' => $price,
            ]);
        }

        Equipment::insert([
            [
                'name' => 'Bambu Lab A1 Mini',
                'type' => 'Máy in FDM',
                'brand' => 'Bambu Lab',
                'model' => 'A1 Mini',
                'serial_number' => 'BL-A1M-001',
                'purchase_date' => '2025-11-15',
                'purchase_price' => 8500000,
                'supplier' => 'Bambu Lab VN',
                'status' => 'active',
                'notes' => 'Máy chính in sản phẩm nhỏ',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Elegoo Mars 4',
                'type' => 'Máy in Resin',
                'brand' => 'Elegoo',
                'model' => 'Mars 4',
                'serial_number' => 'EL-M4-002',
                'purchase_date' => '2026-01-20',
                'purchase_price' => 6200000,
                'supplier' => 'Elegoo Official',
                'status' => 'active',
                'notes' => 'In chi tiết cao',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Máy rửa & sấy resin',
                'type' => 'Phụ trợ',
                'brand' => 'Anycubic',
                'model' => 'Wash & Cure 3',
                'serial_number' => 'AC-WC3-003',
                'purchase_date' => '2026-01-20',
                'purchase_price' => 2800000,
                'supplier' => 'Anycubic Store',
                'status' => 'active',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $products = [
            [
                'category_id' => $categories[0]->id,
                'name' => 'Tượng rồng mini',
                'sku' => '3D-DRG-001',
                'short_description' => 'Mô hình rồng trang trí bàn làm việc, in PLA chi tiết.',
                'description' => "Tượng rồng mini in 3D với độ chi tiết cao.\nPhù hợp làm quà tặng hoặc trang trí góc làm việc.\nHoàn thiện mịn, sơn tùy chọn.",
                'price' => 250000,
                'sale_price' => 199000,
                'promo_label' => 'Sale',
                'cost_price' => 85000,
                'stock' => 12,
                'material_used' => 'PLA Trắng',
                'weight_grams' => 45,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories[1]->id,
                'name' => 'Giá đỡ điện thoại hex',
                'sku' => '3D-STD-002',
                'short_description' => 'Giá đỡ điện thoại hình lục giác chắc chắn.',
                'description' => 'Giá đỡ điện thoại thiết kế tối giản, in PETG chịu lực tốt. Góc nghiêng thoải mái khi xem video.',
                'price' => 89000,
                'sale_price' => 69000,
                'promo_label' => '-22%',
                'cost_price' => 28000,
                'stock' => 30,
                'material_used' => 'PETG',
                'weight_grams' => 35,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories[2]->id,
                'name' => 'Mô hình robot lắp ráp',
                'sku' => '3D-TOY-003',
                'short_description' => 'Robot lắp ráp 12 chi tiết, an toàn cho trẻ lớn.',
                'description' => 'Bộ mô hình robot gồm 12 chi tiết in PLA. Lắp ráp không cần keo, có thể sơn sau khi lắp.',
                'price' => 180000,
                'cost_price' => 65000,
                'stock' => 8,
                'material_used' => 'PLA Đen + PLA Trắng',
                'weight_grams' => 95,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories[3]->id,
                'name' => 'Bánh răng module 1',
                'sku' => '3D-GEAR-004',
                'short_description' => 'Bánh răng kỹ thuật module 1, 20 răng.',
                'description' => 'Chi tiết bánh răng in PETG, dùng cho nguyên mẫu cơ khí hoặc đồ án học sinh.',
                'price' => 45000,
                'cost_price' => 12000,
                'stock' => 50,
                'material_used' => 'PETG',
                'weight_grams' => 12,
                'is_featured' => false,
            ],
            [
                'category_id' => $categories[0]->id,
                'name' => 'Bình hoa hình học',
                'sku' => '3D-VAS-005',
                'short_description' => 'Bình hoa phong cách geometric, in resin mịn.',
                'description' => 'Bình hoa trang trí in resin độ phân giải cao. Chỉ dùng hoa giả (không chứa nước).',
                'price' => 320000,
                'sale_price' => 279000,
                'promo_label' => 'Flash',
                'cost_price' => 110000,
                'stock' => 5,
                'material_used' => 'Resin Standard',
                'weight_grams' => 120,
                'is_featured' => true,
            ],
            [
                'category_id' => $categories[1]->id,
                'name' => 'Móc khóa logo custom',
                'sku' => '3D-KEY-006',
                'short_description' => 'Móc khóa in 3D theo logo yêu cầu.',
                'description' => 'Móc khóa cá nhân hóa. Gửi file logo hoặc text, nhận sản phẩm sau 1–2 ngày in.',
                'price' => 35000,
                'cost_price' => 8000,
                'stock' => 100,
                'material_used' => 'PLA',
                'weight_grams' => 8,
                'is_featured' => false,
            ],
        ];

        foreach ($products as $item) {
            Product::create(array_merge($item, ['is_active' => true]));
        }
    }
}
