<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Page;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::setMany([
            'site_name' => '3D Print Shop',
            'site_tagline' => 'Xưởng in 3D – Sản phẩm sáng tạo & chất lượng',
            'meta_title' => '3D Print Shop | Sản phẩm in 3D, mô hình, quà tặng doanh nghiệp',
            'meta_description' => 'Chuyên in 3D mô hình trang trí, phụ kiện, quà tặng doanh nghiệp. Nhận thiết kế theo yêu cầu, giao hàng toàn quốc.',
            'meta_keywords' => 'in 3D, sản phẩm in 3D, mô hình 3D, quà tặng in 3D, PLA, PETG, resin',
            'phone' => '0901 234 567',
            'hotline' => '0901 234 567',
            'email' => 'lienhe@3dprintshop.vn',
            'address' => 'Hà Nội, Việt Nam',
            'working_hours' => 'T2–T7: 8:00–18:00',
            'facebook' => 'https://facebook.com',
            'zalo' => 'https://zalo.me',
            'youtube' => '',
            'footer_about' => "3D Print Shop chuyên sản xuất và cung cấp sản phẩm in 3D cao cấp.\nNhận gia công theo file thiết kế, tư vấn vật liệu và hoàn thiện bề mặt.",
            'footer_copyright' => '© '.date('Y').' 3D Print Shop. All rights reserved.',
            'home_about_title' => 'Về 3D Print Shop',
            'home_about_content' => "Chúng tôi là xưởng in 3D với máy in FDM và Resin, phục vụ cả khách lẻ lẫn doanh nghiệp.\nMỗi sản phẩm đều được kiểm tra chất lượng trước khi giao.",
            'home_why_title' => 'Vì sao chọn 3D Print Shop',
            'home_why_content' => 'Cam kết chất lượng in, đúng deadline, hỗ trợ chỉnh file và chọn vật liệu phù hợp ngân sách.',
            'google_analytics' => '',
            'geo_region' => 'VN-HN',
            'geo_placename' => '3D Print Shop',
            'geo_position' => '21.0285;105.8542',
        ]);

        Banner::query()->delete();
        Banner::insert([
            [
                'title' => 'Sản phẩm in 3D cao cấp',
                'subtitle' => 'Mô hình – Phụ kiện – Quà tặng doanh nghiệp',
                'image' => null,
                'link' => '/san-pham',
                'button_text' => 'Xem sản phẩm',
                'position' => 'home_slider',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Nhận in theo yêu cầu',
                'subtitle' => 'Gửi file STL / STEP – tư vấn vật liệu miễn phí',
                'image' => null,
                'link' => '/trang/lien-he',
                'button_text' => 'Liên hệ ngay',
                'position' => 'home_slider',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Quà tặng doanh nghiệp',
                'subtitle' => null,
                'image' => null,
                'link' => '/san-pham',
                'button_text' => null,
                'position' => 'home_promo',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Mô hình trang trí',
                'subtitle' => null,
                'image' => null,
                'link' => '/san-pham?category=mo-hinh-trang-tri',
                'button_text' => null,
                'position' => 'home_promo',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Phụ kiện kỹ thuật',
                'subtitle' => null,
                'image' => null,
                'link' => '/san-pham?category=linh-kien-ky-thuat',
                'button_text' => null,
                'position' => 'home_promo',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Page::updateOrCreate(
            ['slug' => 'gioi-thieu'],
            [
                'title' => 'Giới thiệu',
                'content' => "3D Print Shop là đơn vị chuyên in 3D sản phẩm mô hình, quà tặng và chi tiết kỹ thuật.\n\nChúng tôi sử dụng máy in FDM và Resin, đa dạng vật liệu PLA, PETG, ABS, Resin.\nCam kết chất lượng, tiến độ và hỗ trợ khách hàng trong suốt quá trình đặt hàng.",
                'meta_title' => 'Giới thiệu 3D Print Shop | Xưởng in 3D chuyên nghiệp',
                'meta_description' => 'Tìm hiểu về 3D Print Shop – xưởng in 3D mô hình, quà tặng doanh nghiệp và chi tiết kỹ thuật.',
                'meta_keywords' => 'giới thiệu in 3D, xưởng in 3D',
                'is_published' => true,
                'show_in_menu' => true,
                'sort_order' => 1,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'lien-he'],
            [
                'title' => 'Liên hệ',
                'content' => "Liên hệ đặt hàng / tư vấn in 3D:\n\nHotline: 0901 234 567\nEmail: lienhe@3dprintshop.vn\nĐịa chỉ: Hà Nội, Việt Nam\nGiờ làm việc: T2–T7, 8:00–18:00\n\nGửi file thiết kế để nhận báo giá nhanh trong ngày.",
                'meta_title' => 'Liên hệ 3D Print Shop | Tư vấn & báo giá in 3D',
                'meta_description' => 'Liên hệ 3D Print Shop để tư vấn vật liệu, báo giá in 3D và đặt hàng theo yêu cầu.',
                'meta_keywords' => 'liên hệ in 3D, báo giá in 3D',
                'is_published' => true,
                'show_in_menu' => true,
                'sort_order' => 2,
            ]
        );

        Page::updateOrCreate(
            ['slug' => 'chinh-sach-bao-hanh'],
            [
                'title' => 'Chính sách bảo hành',
                'content' => "Sản phẩm in 3D được kiểm tra trước khi giao.\nHỗ trợ đổi/trả trong 7 ngày nếu lỗi do sản xuất.\nKhông áp dụng với sản phẩm đã gia công sơn theo yêu cầu riêng (trừ lỗi kỹ thuật).",
                'meta_title' => 'Chính sách bảo hành | 3D Print Shop',
                'meta_description' => 'Chính sách bảo hành, đổi trả sản phẩm in 3D tại 3D Print Shop.',
                'is_published' => true,
                'show_in_menu' => true,
                'sort_order' => 3,
            ]
        );

        Post::updateOrCreate(
            ['slug' => 'huong-dan-chon-vat-lieu-in-3d'],
            [
                'title' => 'Hướng dẫn chọn vật liệu in 3D: PLA, PETG, Resin',
                'excerpt' => 'Nên chọn PLA, PETG hay Resin cho sản phẩm của bạn? So sánh nhanh ưu nhược điểm từng loại.',
                'content' => "PLA: dễ in, màu đẹp, phù hợp mô hình trang trí.\nPETG: bền hơn, chịu lực tốt, hợp phụ kiện kỹ thuật.\nResin: độ chi tiết cao, bề mặt mịn, hợp tượng mini và mẫu trưng bày.\n\nLiên hệ 3D Print Shop để được tư vấn vật liệu theo ngân sách và mục đích sử dụng.",
                'meta_title' => 'Chọn vật liệu in 3D: PLA, PETG, Resin | 3D Print Shop',
                'meta_description' => 'So sánh PLA, PETG, Resin để chọn vật liệu in 3D phù hợp mô hình, phụ kiện và quà tặng.',
                'meta_keywords' => 'vật liệu in 3D, PLA, PETG, Resin',
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ]
        );

        Post::updateOrCreate(
            ['slug' => 'quy-trinh-dat-hang-in-3d'],
            [
                'title' => 'Quy trình đặt hàng in 3D tại xưởng',
                'excerpt' => '4 bước đặt hàng in 3D: gửi file – báo giá – in thử – giao hàng.',
                'content' => "1. Gửi file STL/STEP hoặc ý tưởng thiết kế.\n2. Nhận tư vấn vật liệu, thời gian in và báo giá.\n3. In mẫu (nếu cần) và chốt đơn.\n4. Sản xuất hàng loạt, đóng gói và giao hàng toàn quốc.",
                'meta_title' => 'Quy trình đặt hàng in 3D | 3D Print Shop',
                'meta_description' => 'Tìm hiểu quy trình đặt hàng in 3D nhanh gọn tại 3D Print Shop.',
                'meta_keywords' => 'đặt hàng in 3D, gia công in 3D',
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]
        );
    }
}
