<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Support\Trash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrashController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $filter = $request->string('type')->toString();
        $types = Trash::types();
        $labels = Trash::labels();

        if ($filter !== '' && ! isset($types[$filter])) {
            return $this->fail('Loại thùng rác không hợp lệ.', 422);
        }

        $items = collect();

        foreach ($types as $type => $class) {
            if ($filter !== '' && $filter !== $type) {
                continue;
            }

            $query = $class::onlyTrashed()->latest('deleted_at');

            if ($type === 'products') {
                $query->with(['category' => fn ($q) => $q->withTrashed()]);
            }

            if ($type === 'material_inputs') {
                $query->with(['material' => fn ($q) => $q->withTrashed()]);
            }

            foreach ($query->get() as $model) {
                $items->push([
                    'type' => $type,
                    'label' => $labels[$type],
                    'id' => $model->id,
                    'name' => $this->displayName($type, $model),
                    'meta' => $this->displayMeta($type, $model),
                    'deleted_at' => optional($model->deleted_at)->toIso8601String(),
                    'purge_at' => optional(Trash::purgeAt($model))->toIso8601String(),
                    'days_left' => Trash::daysLeft($model),
                ]);
            }
        }

        $items = $items->sortByDesc(fn ($row) => $row['deleted_at'])->values();
        $counts = [];
        foreach ($types as $type => $class) {
            $counts[$type] = $class::onlyTrashed()->count();
        }

        return $this->ok([
            'items' => $items,
            'labels' => $labels,
            'filter' => $filter,
            'counts' => $counts,
            'total' => array_sum($counts),
            'retention_days' => Trash::RETENTION_DAYS,
        ]);
    }

    public function restore(string $type, int $id): JsonResponse
    {
        try {
            $model = Trash::findTrashed($type, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        if ($type === 'material_inputs') {
            $material = Material::withTrashed()->find($model->material_id);
            if (! $material || $material->trashed()) {
                return $this->fail('Không thể khôi phục phiếu nhập vì nguyên liệu liên quan đang trong thùng rác.', 422);
            }
        }

        if ($type === 'products') {
            if (Product::where('slug', $model->slug)->where('id', '!=', $model->id)->exists()) {
                return $this->fail('Không khôi phục được: slug sản phẩm đã được dùng.', 422);
            }
            if ($model->sku && Product::where('sku', $model->sku)->where('id', '!=', $model->id)->exists()) {
                return $this->fail('Không khôi phục được: SKU đã được dùng.', 422);
            }
        }

        if ($type === 'categories') {
            if (Category::where('slug', $model->slug)->where('id', '!=', $model->id)->exists()) {
                return $this->fail('Không khôi phục được: slug danh mục đã được dùng.', 422);
            }
        }

        $name = $this->displayName($type, $model);

        DB::transaction(function () use ($type, $model) {
            if ($type === 'material_inputs') {
                $material = Material::lockForUpdate()->findOrFail($model->material_id);
                $material->stock_quantity = (float) $material->stock_quantity + (float) $model->quantity;
                $material->save();
            }

            $model->restore();
        });

        return $this->ok([
            'type' => $type,
            'id' => $id,
            'name' => $name,
        ], 'Đã khôi phục: '.Trash::labelFor($type).' — '.$name.'.');
    }

    public function forceDelete(string $type, int $id): JsonResponse
    {
        try {
            $model = Trash::findTrashed($type, $id);
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $name = $this->displayName($type, $model);

        if ($type === 'materials') {
            $linked = MaterialInput::withTrashed()->where('material_id', $model->id)->count();
            if ($linked > 0) {
                return $this->fail("Không xóa vĩnh viễn được: nguyên liệu còn {$linked} phiếu nhập.", 422);
            }
        }

        if ($type === 'categories') {
            $linked = Product::withTrashed()->where('category_id', $model->id)->count();
            if ($linked > 0) {
                return $this->fail("Không xóa vĩnh viễn được: danh mục còn {$linked} sản phẩm.", 422);
            }
        }

        DB::transaction(function () use ($type, $model) {
            if (in_array($type, ['products', 'banners', 'posts'], true) && ! empty($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            if (in_array($type, ['products', 'posts', 'pages'], true) && ! empty($model->og_image)) {
                Storage::disk('public')->delete($model->og_image);
            }

            $model->forceDelete();
        });

        return $this->ok(null, 'Đã xóa vĩnh viễn: '.Trash::labelFor($type).' — '.$name.'.');
    }

    public function empty(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $types = Trash::types();

        if ($type !== '' && ! isset($types[$type])) {
            return $this->fail('Loại dữ liệu không hợp lệ.', 422);
        }

        $deleted = 0;
        $skipped = 0;

        $order = ['material_inputs', 'products', 'materials', 'categories', 'equipment', 'banners', 'posts', 'pages'];
        $targets = $type === ''
            ? array_values(array_filter($order, fn ($key) => isset($types[$key])))
            : [$type];

        foreach ($targets as $key) {
            $class = $types[$key];
            $records = $class::onlyTrashed()->get();

            foreach ($records as $model) {
                if ($key === 'materials') {
                    $linked = MaterialInput::withTrashed()->where('material_id', $model->id)->count();
                    if ($linked > 0) {
                        $skipped++;
                        continue;
                    }
                }

                if ($key === 'categories') {
                    $linked = Product::withTrashed()->where('category_id', $model->id)->count();
                    if ($linked > 0) {
                        $skipped++;
                        continue;
                    }
                }

                if (in_array($key, ['products', 'banners', 'posts'], true) && ! empty($model->image)) {
                    Storage::disk('public')->delete($model->image);
                }
                if (in_array($key, ['products', 'posts', 'pages'], true) && ! empty($model->og_image)) {
                    Storage::disk('public')->delete($model->og_image);
                }

                $model->forceDelete();
                $deleted++;
            }
        }

        $message = "Đã dọn thùng rác: xóa vĩnh viễn {$deleted} mục.";
        if ($skipped > 0) {
            $message .= " Bỏ qua {$skipped} mục do còn ràng buộc dữ liệu.";
        }

        return $this->ok([
            'deleted' => $deleted,
            'skipped' => $skipped,
        ], $message);
    }

    private function displayName(string $type, $model): string
    {
        return match ($type) {
            'material_inputs' => sprintf(
                'Phiếu #%d — %s (%s)',
                $model->id,
                $model->material?->name ?? 'N/A',
                optional($model->input_date)->format('d/m/Y') ?? '—'
            ),
            'posts', 'pages', 'banners' => $model->title ?? ('#'.$model->id),
            default => $model->name ?? ('#'.$model->id),
        };
    }

    private function displayMeta(string $type, $model): string
    {
        return match ($type) {
            'products' => ($model->sku ? $model->sku.' · ' : '').number_format((float) $model->price, 0, ',', '.').' đ',
            'categories' => $model->slug,
            'materials' => trim(($model->type ?? '').' · '.($model->color ?? ''), ' ·'),
            'material_inputs' => number_format((float) $model->total_price, 0, ',', '.').' đ',
            'equipment' => trim(($model->brand ?? '').' '.($model->model ?? '')),
            'banners' => $model->position ?? '',
            'posts', 'pages' => $model->slug ?? '',
            default => '',
        };
    }
}
