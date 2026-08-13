<?php

namespace App\Console\Commands;

use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Support\Trash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeTrashCommand extends Command
{
    protected $signature = 'trash:purge {--days= : Số ngày giữ trong thùng rác (mặc định 30)}';

    protected $description = 'Xóa vĩnh viễn các bản ghi trong thùng rác đã quá thời gian giữ (mặc định 30 ngày)';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: Trash::RETENTION_DAYS);
        $threshold = now()->subDays($days);
        $purged = 0;
        $skipped = 0;

        // Thứ tự an toàn khóa ngoại
        $order = ['material_inputs', 'products', 'materials', 'categories', 'equipment', 'banners', 'posts', 'pages'];

        foreach ($order as $type) {
            $class = Trash::modelClass($type);
            $records = $class::onlyTrashed()
                ->where('deleted_at', '<=', $threshold)
                ->get();

            foreach ($records as $model) {
                if ($type === 'materials') {
                    $linked = MaterialInput::withTrashed()->where('material_id', $model->id)->count();
                    if ($linked > 0) {
                        $skipped++;
                        $this->warn("Bỏ qua material #{$model->id}: còn {$linked} phiếu nhập.");
                        continue;
                    }
                }

                if ($type === 'categories') {
                    $linked = Product::withTrashed()->where('category_id', $model->id)->count();
                    if ($linked > 0) {
                        $skipped++;
                        $this->warn("Bỏ qua category #{$model->id}: còn {$linked} sản phẩm.");
                        continue;
                    }
                }

                if (in_array($type, ['products', 'banners', 'posts'], true) && ! empty($model->image)) {
                    Storage::disk('public')->delete($model->image);
                }
                if (in_array($type, ['products', 'posts', 'pages'], true) && ! empty($model->og_image)) {
                    Storage::disk('public')->delete($model->og_image);
                }

                $model->forceDelete();
                $purged++;
            }
        }

        $this->info("Đã xóa vĩnh viễn {$purged} bản ghi quá {$days} ngày trong thùng rác.");
        if ($skipped > 0) {
            $this->warn("Bỏ qua {$skipped} bản ghi do còn ràng buộc.");
        }

        return self::SUCCESS;
    }
}
