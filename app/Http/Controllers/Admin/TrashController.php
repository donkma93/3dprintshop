<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Support\Trash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrashController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->string('type')->toString();
        $types = Trash::types();
        $labels = Trash::labels();

        if ($filter !== '' && ! isset($types[$filter])) {
            $filter = '';
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
                    'deleted_at' => $model->deleted_at,
                    'purge_at' => Trash::purgeAt($model),
                    'days_left' => Trash::daysLeft($model),
                ]);
            }
        }

        $items = $items->sortByDesc(fn ($row) => $row['deleted_at'])->values();
        $counts = [];
        foreach ($types as $type => $class) {
            $counts[$type] = $class::onlyTrashed()->count();
        }
        $total = array_sum($counts);

        return view('admin.trash.index', [
            'items' => $items,
            'labels' => $labels,
            'filter' => $filter,
            'counts' => $counts,
            'total' => $total,
            'retentionDays' => Trash::RETENTION_DAYS,
        ]);
    }

    public function restore(string $type, int $id)
    {
        $model = Trash::findTrashed($type, $id);

        if ($type === 'material_inputs') {
            /** @var MaterialInput $model */
            $material = Material::withTrashed()->find($model->material_id);
            if (! $material || $material->trashed()) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không thể khôi phục phiếu nhập vì nguyên liệu liên quan đang trong thùng rác hoặc không còn. Hãy khôi phục nguyên liệu trước.');
            }
        }

        if ($type === 'products') {
            /** @var Product $model */
            if (Product::where('slug', $model->slug)->where('id', '!=', $model->id)->exists()) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không khôi phục được: slug sản phẩm đã được dùng bởi bản ghi khác.');
            }
            if ($model->sku && Product::where('sku', $model->sku)->where('id', '!=', $model->id)->exists()) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không khôi phục được: SKU đã được dùng bởi sản phẩm khác.');
            }
        }

        if ($type === 'categories') {
            /** @var Category $model */
            if (Category::where('slug', $model->slug)->where('id', '!=', $model->id)->exists()) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không khôi phục được: slug danh mục đã được dùng bởi bản ghi khác.');
            }
        }

        $name = $this->displayName($type, $model);

        DB::transaction(function () use ($type, $model) {
            if ($type === 'material_inputs') {
                /** @var MaterialInput $model */
                // withTrashed không cần ở đây vì đã chặn restore khi NL còn trong thùng rác;
                // vẫn lock an toàn theo id active.
                $material = Material::lockForUpdate()->findOrFail($model->material_id);
                $material->stock_quantity = (float) $material->stock_quantity + (float) $model->quantity;
                $material->save();
            }

            $model->restore();
        });

        return redirect()
            ->route('admin.trash.index', request()->only('type'))
            ->with('success', 'Đã khôi phục: '.Trash::labelFor($type).' — '.$name.'.');
    }

    public function forceDelete(string $type, int $id)
    {
        $model = Trash::findTrashed($type, $id);
        $name = $this->displayName($type, $model);

        if ($type === 'materials') {
            /** @var Material $model */
            $linked = MaterialInput::withTrashed()->where('material_id', $model->id)->count();
            if ($linked > 0) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không xóa vĩnh viễn được: nguyên liệu còn '.$linked.' phiếu nhập (kể cả trong thùng rác). Hãy xóa vĩnh viễn các phiếu nhập trước.');
            }
        }

        if ($type === 'categories') {
            /** @var Category $model */
            $linked = Product::withTrashed()->where('category_id', $model->id)->count();
            if ($linked > 0) {
                return redirect()
                    ->route('admin.trash.index', request()->only('type'))
                    ->with('error', 'Không xóa vĩnh viễn được: danh mục còn '.$linked.' sản phẩm (kể cả trong thùng rác).');
            }
        }

        DB::transaction(function () use ($type, $model) {
            if (in_array($type, ['products', 'banners', 'posts'], true) && ! empty($model->image)) {
                Storage::disk('public')->delete($model->image);
            }
            if (in_array($type, ['products', 'posts', 'pages'], true) && ! empty($model->og_image)) {
                Storage::disk('public')->delete($model->og_image);
            }

            // material_inputs: tồn kho đã trừ khi đưa vào thùng rác
            $model->forceDelete();
        });

        return redirect()
            ->route('admin.trash.index', request()->only('type'))
            ->with('success', 'Đã xóa vĩnh viễn: '.Trash::labelFor($type).' — '.$name.'.');
    }

    public function empty(Request $request)
    {
        $type = $request->string('type')->toString();
        $types = Trash::types();

        if ($type !== '' && ! isset($types[$type])) {
            return back()->with('error', 'Loại dữ liệu không hợp lệ.');
        }

        $deleted = 0;
        $skipped = 0;

        // Xóa theo thứ tự an toàn FK: phiếu nhập → sản phẩm → còn lại
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

        return redirect()
            ->route('admin.trash.index')
            ->with('success', $message);
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
