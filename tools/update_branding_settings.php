<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SiteSetting;

SiteSetting::setMany([
    'site_name' => 'Shop3DPrinting',
    'site_tagline' => 'Tận tâm - từ tấm lòng',
    'meta_title' => 'Shop3DPrinting | Tận tâm - từ tấm lòng',
    'meta_description' => 'Shop3DPrinting — Tận tâm - từ tấm lòng. Chuyên in 3D mô hình, phụ kiện, quà tặng doanh nghiệp.',
    'footer_about' => "Shop3DPrinting — Tận tâm - từ tấm lòng.\nChuyên sản xuất và cung cấp sản phẩm in 3D cao cấp. Nhận gia công theo file thiết kế.",
    'footer_copyright' => '© '.date('Y').' Shop3DPrinting. All rights reserved.',
    'home_about_title' => 'Về Shop3DPrinting',
    'home_why_title' => 'Vì sao chọn Shop3DPrinting',
    'geo_placename' => 'Shop3DPrinting',
    'logo' => 'branding/logo.png',
    'favicon' => 'branding/favicon.png',
    'og_image' => 'branding/og.png',
]);

echo "Branding settings updated.\n";
print_r(SiteSetting::pluck('value', 'key')->only([
    'site_name', 'site_tagline', 'logo', 'favicon', 'meta_title',
])->toArray());
