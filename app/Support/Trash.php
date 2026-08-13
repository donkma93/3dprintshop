<?php

namespace App\Support;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Trash
{
    /** Số ngày giữ trong thùng rác trước khi xóa vĩnh viễn */
    public const RETENTION_DAYS = 30;

    /**
     * @return array<string, class-string<Model>>
     */
    public static function types(): array
    {
        return [
            'categories' => Category::class,
            'products' => Product::class,
            'materials' => Material::class,
            'material_inputs' => MaterialInput::class,
            'equipment' => Equipment::class,
            'banners' => Banner::class,
            'posts' => Post::class,
            'pages' => Page::class,
        ];
    }

    public static function labels(): array
    {
        return [
            'categories' => 'Danh mục',
            'products' => 'Sản phẩm',
            'materials' => 'Nguyên liệu',
            'material_inputs' => 'Phiếu nhập nguyên liệu',
            'equipment' => 'Thiết bị',
            'banners' => 'Banner',
            'posts' => 'Bài viết',
            'pages' => 'Trang tĩnh',
        ];
    }

    public static function modelClass(string $type): string
    {
        $types = static::types();

        if (! isset($types[$type])) {
            throw new InvalidArgumentException("Loại thùng rác không hợp lệ: {$type}");
        }

        return $types[$type];
    }

    public static function findTrashed(string $type, int $id): Model
    {
        $class = static::modelClass($type);

        return $class::onlyTrashed()->findOrFail($id);
    }

    public static function labelFor(string $type): string
    {
        return static::labels()[$type] ?? $type;
    }

    public static function daysLeft(Model $model): int
    {
        $purgeAt = static::purgeAt($model);

        if (! $purgeAt || $purgeAt->lte(now())) {
            return 0;
        }

        // Làm tròn lên theo ngày lịch (vd: 29.1 ngày → còn 30 ngày hiển thị)
        return max(1, (int) ceil(now()->floatDiffInDays($purgeAt)));
    }

    public static function purgeAt(Model $model): ?\Carbon\Carbon
    {
        if (! $model->deleted_at) {
            return null;
        }

        return $model->deleted_at->copy()->addDays(static::RETENTION_DAYS);
    }
}
