<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Slider + bài viết giới thiệu mô hình in 3D, dùng ảnh mock đã có trong storage.
 */
class SliderAndPostsSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('public');
        $disk->makeDirectory('banners');
        $disk->makeDirectory('posts');

        $copy = function (string $fromRel, string $toRel) use ($disk): ?string {
            $src = storage_path('app/public/'.$fromRel);
            if (! is_file($src) || filesize($src) < 5000) {
                $this->command?->warn("Thiếu ảnh nguồn: {$fromRel}");

                return null;
            }
            $dest = storage_path('app/public/'.$toRel);
            File::ensureDirectoryExists(dirname($dest));
            File::copy($src, $dest);

            return $toRel;
        };

        // —— Home slider (ảnh rộng, giới thiệu mô hình) ——
        $slides = [
            [
                'title' => 'Thế giới mô hình in 3D',
                'subtitle' => 'Rồng mini, bình hoa geometric, tượng trang trí – chi tiết sắc nét, sẵn sàng trưng bày.',
                'from' => 'products/mock_dragon.jpg',
                'to' => 'banners/slider-dragon.jpg',
                'link' => '/san-pham?category=mo-hinh-trang-tri',
                'button_text' => 'Khám phá mô hình',
                'sort_order' => 1,
            ],
            [
                'title' => 'Bình hoa hình học độc đáo',
                'subtitle' => 'Thiết kế parametric, in FDM/Resin – điểm nhấn nội thất hiện đại.',
                'from' => 'products/mock_vase1.jpg',
                'to' => 'banners/slider-vase.jpg',
                'link' => '/san-pham',
                'button_text' => 'Xem bộ sưu tập',
                'sort_order' => 2,
            ],
            [
                'title' => 'Linh kiện & bánh răng kỹ thuật',
                'subtitle' => 'Prototype cơ khí, bánh răng helical – in PETG chịu lực, đúng form-fit.',
                'from' => 'products/mock_gear_helical.jpg',
                'to' => 'banners/slider-gear.jpg',
                'link' => '/san-pham?category=linh-kien-ky-thuat',
                'button_text' => 'Xem linh kiện',
                'sort_order' => 3,
            ],
            [
                'title' => 'Phụ kiện desk setup',
                'subtitle' => 'Giá đỡ điện thoại, móc khóa custom, mô hình tech – in nhanh giao toàn quốc.',
                'from' => 'products/mock_phone_stand.jpg',
                'to' => 'banners/slider-stand.jpg',
                'link' => '/san-pham?category=phu-kien',
                'button_text' => 'Mua phụ kiện',
                'sort_order' => 4,
            ],
            [
                'title' => 'Miniature & đồ chơi lắp ráp',
                'subtitle' => 'Robot, tượng mini, mô hình sưu tầm – an toàn, dễ sơn, phù hợp quà tặng.',
                'from' => 'products/mock_miniature.jpg',
                'to' => 'banners/slider-mini.jpg',
                'link' => '/san-pham?category=do-choi',
                'button_text' => 'Xem đồ chơi',
                'sort_order' => 5,
            ],
        ];

        Banner::where('position', 'home_slider')->delete();

        foreach ($slides as $slide) {
            $image = $copy($slide['from'], $slide['to']);
            Banner::create([
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'image' => $image,
                'link' => $slide['link'],
                'button_text' => $slide['button_text'],
                'position' => 'home_slider',
                'sort_order' => $slide['sort_order'],
                'is_active' => true,
            ]);
            $this->command?->info('Slider: '.$slide['title']);
        }

        // —— Promo tiles ——
        $promos = [
            [
                'title' => 'Mô hình trang trí',
                'from' => 'products/mock_figurine1.png',
                'to' => 'banners/promo-decor.png',
                'link' => '/san-pham?category=mo-hinh-trang-tri',
                'sort_order' => 1,
            ],
            [
                'title' => 'Bình hoa geometric',
                'from' => 'products/mock_vase3.jpg',
                'to' => 'banners/promo-vase.jpg',
                'link' => '/san-pham',
                'sort_order' => 2,
            ],
            [
                'title' => 'Linh kiện kỹ thuật',
                'from' => 'products/mock_gear_triple.jpg',
                'to' => 'banners/promo-gear.jpg',
                'link' => '/san-pham?category=linh-kien-ky-thuat',
                'sort_order' => 3,
            ],
        ];

        Banner::where('position', 'home_promo')->delete();
        foreach ($promos as $promo) {
            $image = $copy($promo['from'], $promo['to']);
            Banner::create([
                'title' => $promo['title'],
                'subtitle' => null,
                'image' => $image,
                'link' => $promo['link'],
                'button_text' => null,
                'position' => 'home_promo',
                'sort_order' => $promo['sort_order'],
                'is_active' => true,
            ]);
            $this->command?->info('Promo: '.$promo['title']);
        }

        // —— Bài viết giới thiệu mô hình in 3D ——
        $posts = [
            [
                'slug' => 'tuong-rong-mini-in-3d',
                'title' => 'Tượng rồng mini in 3D: chi tiết sắc, làm điểm nhấn bàn làm việc',
                'excerpt' => 'Mô hình rồng mini in PLA/Resin – bề mặt mịn, tỉ lệ nhỏ gọn, phù hợp quà tặng và trang trí.',
                'content' => "Tượng rồng mini là một trong những mẫu in 3D được ưa chuộng nhất cho góc làm việc và kệ trưng bày.\n\n"
                    ."**Đặc điểm nổi bật**\n"
                    ."- In FDM (PLA) hoặc Resin để đạt độ chi tiết vảy, sừng, râu.\n"
                    ."- Kích thước gọn, dễ đặt bàn / kệ sách.\n"
                    ."- Có thể sơn acrylic sau khi in để tăng chiều sâu.\n\n"
                    ."**Gợi ý vật liệu**\n"
                    ."- PLA: màu đẹp, dễ in, hợp trang trí.\n"
                    ."- Resin: chi tiết cực mịn, hợp sưu tầm.\n\n"
                    .'Xem ngay sản phẩm **Tượng rồng mini** trên cửa hàng hoặc liên hệ để in theo file STL của bạn.',
                'image_from' => 'products/mock_dragon.jpg',
                'image_to' => 'posts/post-dragon.jpg',
                'days_ago' => 6,
            ],
            [
                'slug' => 'binh-hoa-hinh-hoc-in-3d',
                'title' => 'Bình hoa hình học in 3D: geometric decor cho không gian hiện đại',
                'excerpt' => 'Bình hoa parametric, form xoắn / đa diện – in 3D tạo điểm nhấn nội thất tối giản.',
                'content' => "Bình hoa geometric là mẫu decor “ăn điểm” nhờ form lạ và lớp in đều.\n\n"
                    ."**Vì sao nên chọn in 3D?**\n"
                    ."- Form phức tạp (xoắn, tổ ong, đa diện) khó làm thủ công.\n"
                    ."- Đổi màu / scale dễ dàng theo không gian.\n"
                    ."- Chỉ dùng hoa giả hoặc cắm khô (với PLA/PETG thường).\n\n"
                    ."**Bộ sưu tập tại shop**\n"
                    ."- Bình hoa hình học\n"
                    ."- Bình hoa geometric cam\n"
                    ."- Bình hoa xoắn 3D\n\n"
                    .'Đặt hàng theo màu yêu cầu – giao toàn quốc.',
                'image_from' => 'products/mock_vase1.jpg',
                'image_to' => 'posts/post-vase.jpg',
                'days_ago' => 5,
            ],
            [
                'slug' => 'banh-rang-va-linh-kien-ky-thuat-in-3d',
                'title' => 'Bánh răng & linh kiện kỹ thuật in 3D: prototype nhanh, đúng form-fit',
                'excerpt' => 'In PETG bánh răng module, bộ helical planetary – kiểm tra lắp ráp trước khi gia công kim loại.',
                'content' => "In 3D giúp rút ngắn chu kỳ prototype cơ khí từ tuần xuống còn vài giờ.\n\n"
                    ."**Ứng dụng**\n"
                    ."- Bánh răng module 1 / helical planetary demo.\n"
                    ."- Gripper, khớp nối, vỏ bảo vệ linh kiện.\n"
                    ."- Mockup form-fit-function trước CNC.\n\n"
                    ."**Vật liệu gợi ý**\n"
                    ."- PETG: bền, dẻo dai, hợp chi tiết chịu lực nhẹ.\n"
                    ."- ABS: chịu nhiệt tốt hơn (cần máy kín).\n\n"
                    .'Gửi file STEP/STL để nhận báo giá trong ngày.',
                'image_from' => 'products/mock_gear_helical.jpg',
                'image_to' => 'posts/post-gear.jpg',
                'days_ago' => 4,
            ],
            [
                'slug' => 'phu-kien-desk-setup-in-3d',
                'title' => 'Phụ kiện desk setup in 3D: giá đỡ điện thoại & móc khóa custom',
                'excerpt' => 'Giá đỡ hex, móc khóa logo, chi tiết tech – in nhanh, cá nhân hóa theo yêu cầu.',
                'content' => "Desk setup gọn – đẹp nhờ phụ kiện in 3D đúng ý.\n\n"
                    ."**Sản phẩm nổi bật**\n"
                    ."- Giá đỡ điện thoại hex: góc xem video thoải mái, in PETG chắc.\n"
                    ."- Móc khóa logo custom: gửi file logo, nhận sau 1–2 ngày.\n"
                    ."- Mô hình tech showcase cho góc làm việc.\n\n"
                    ."**Lợi ích**\n"
                    ."- In theo màu brand / logo công ty.\n"
                    ."- Số lượng linh hoạt: 1 cái đến hàng trăm.\n\n"
                    .'Phù hợp quà tặng sự kiện, onboarding nhân sự, booth triển lãm.',
                'image_from' => 'products/mock_phone_stand.jpg',
                'image_to' => 'posts/post-stand.jpg',
                'days_ago' => 3,
            ],
            [
                'slug' => 'mo-hinh-miniature-va-do-choi-lap-rap',
                'title' => 'Miniature & robot lắp ráp in 3D: sưu tầm, tặng quà, học STEM',
                'excerpt' => 'Mô hình tỉ lệ nhỏ, robot 12 chi tiết – in an toàn, dễ sơn, kích thích sáng tạo.',
                'content' => "In 3D mở ra thế giới đồ chơi và mô hình sưu tầm không giới hạn khuôn mẫu.\n\n"
                    ."**Gợi ý sản phẩm**\n"
                    ."- Mô hình miniature sưu tầm\n"
                    ."- Mô hình robot lắp ráp (không cần keo)\n"
                    ."- Tượng mini phong cách cổ\n\n"
                    ."**Tips hoàn thiện**\n"
                    ."- Chà nhám 400–800, sơn lót rồi acrylic.\n"
                    ."- Resin cho chi tiết khuôn mặt / vảy; PLA cho thân to.\n\n"
                    .'Phù hợp quà sinh nhật, workshop STEM cho học sinh.',
                'image_from' => 'products/mock_miniature.jpg',
                'image_to' => 'posts/post-mini.jpg',
                'days_ago' => 2,
            ],
            [
                'slug' => 'quy-trinh-in-va-chon-mo-hinh-3d',
                'title' => 'Từ ý tưởng đến mô hình in 3D: chọn mẫu, vật liệu và hoàn thiện',
                'excerpt' => 'Hướng dẫn chọn mô hình phù hợp mục đích – trang trí, kỹ thuật hay quà tặng – và quy trình in tại xưởng.',
                'content' => "Một mô hình in 3D đẹp bắt đầu từ đúng mục đích sử dụng.\n\n"
                    ."**1. Chọn loại mô hình**\n"
                    ."- Trang trí: rồng, bình hoa, geometric.\n"
                    ."- Kỹ thuật: bánh răng, prototype linh kiện.\n"
                    ."- Phụ kiện: giá đỡ, móc khóa, desk setup.\n\n"
                    ."**2. Chọn vật liệu**\n"
                    ."- PLA / Resin: đẹp, chi tiết.\n"
                    ."- PETG / ABS: bền, chịu lực.\n\n"
                    ."**3. Quy trình tại xưởng**\n"
                    ."Gửi file → tư vấn → in thử (nếu cần) → sản xuất → đóng gói giao hàng.\n\n"
                    .'Duyệt catalog trên trang chủ hoặc chat hotline để được gợi ý mẫu phù hợp.',
                'image_from' => 'products/mock_print1.jpg',
                'image_to' => 'posts/post-process.jpg',
                'days_ago' => 1,
            ],
        ];

        foreach ($posts as $item) {
            $image = $copy($item['image_from'], $item['image_to']);
            Post::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'content' => $item['content'],
                    'image' => $image,
                    'meta_title' => $item['title'].' | 3D Print Shop',
                    'meta_description' => $item['excerpt'],
                    'meta_keywords' => 'mô hình in 3D, in 3D, 3D print',
                    'is_published' => true,
                    'published_at' => now()->subDays($item['days_ago']),
                ]
            );
            $this->command?->info('Post: '.$item['title']);
        }

        // Gán ảnh cho 2 bài cũ nếu còn
        $legacy = [
            'huong-dan-chon-vat-lieu-in-3d' => ['products/mock_print3.jpg', 'posts/post-material.jpg'],
            'quy-trinh-dat-hang-in-3d' => ['products/mock_tech1.jpg', 'posts/post-order.jpg'],
        ];
        foreach ($legacy as $slug => [$from, $to]) {
            $post = Post::where('slug', $slug)->first();
            if (! $post) {
                continue;
            }
            $image = $copy($from, $to);
            if ($image) {
                $post->image = $image;
                $post->save();
            }
        }

        $this->command?->info(
            'Sliders: '.Banner::where('position', 'home_slider')->count()
            .' | Promos: '.Banner::where('position', 'home_promo')->count()
            .' | Posts: '.Post::count()
        );
    }
}
