<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\MaterialResource;
use App\Models\Category;
use App\Models\ChatConversation;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function index(): JsonResponse
    {
        $user = request()->user();
        $canRevenue = $user->canViewRevenue();

        $stats = [
            'products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'categories' => Category::count(),
            'materials' => Material::count(),
            'low_stock_materials' => Material::whereColumn('stock_quantity', '<=', 'min_stock')->count(),
            'equipment' => Equipment::count(),
            'material_inputs' => MaterialInput::count(),
            'open_chats' => ChatConversation::where('status', 'open')->count(),
        ];

        $charts = [
            'months' => collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('m/Y'))->values()->all(),
            'products_created' => $this->countByMonth(Product::query(), 'created_at'),
            'inputs_count' => $this->countByMonth(MaterialInput::query(), 'created_at'),
            'stock_overview' => [
                'labels' => ['Sản phẩm active', 'Sản phẩm ẩn', 'NL ổn định', 'NL sắp hết'],
                'values' => [
                    Product::where('is_active', true)->count(),
                    Product::where('is_active', false)->count(),
                    max(0, Material::count() - Material::whereColumn('stock_quantity', '<=', 'min_stock')->count()),
                    Material::whereColumn('stock_quantity', '<=', 'min_stock')->count(),
                ],
            ],
            'categories' => [
                'labels' => Category::withCount('products')->orderByDesc('products_count')->take(8)->pluck('name')->all(),
                'values' => Category::withCount('products')->orderByDesc('products_count')->take(8)->pluck('products_count')->all(),
            ],
        ];

        if ($canRevenue) {
            $stats['material_stock_value'] = (float) Material::get()->sum(fn ($m) => $m->stock_value);
            $stats['equipment_value'] = (float) Equipment::sum('purchase_price');
            $stats['catalog_sales_value'] = (float) Product::where('is_active', true)
                ->get()
                ->sum(fn ($p) => (float) $p->price * (int) $p->stock);
            $stats['catalog_cost_value'] = (float) Product::where('is_active', true)
                ->get()
                ->sum(fn ($p) => (float) $p->cost_price * (int) $p->stock);
            $stats['potential_margin'] = $stats['catalog_sales_value'] - $stats['catalog_cost_value'];
            $stats['inputs_total_30d'] = (float) MaterialInput::where('input_date', '>=', now()->subDays(30))->sum('total_price');
            $stats['inputs_total_all'] = (float) MaterialInput::sum('total_price');

            $charts['inputs_spend'] = $this->sumByMonth(MaterialInput::query(), 'total_price', 'input_date');
            $charts['asset_breakdown'] = [
                'labels' => ['Giá trị tồn NL', 'Giá trị thiết bị', 'Giá trị SP (giá bán×kho)', 'Giá vốn SP×kho'],
                'values' => [
                    $stats['material_stock_value'],
                    $stats['equipment_value'],
                    $stats['catalog_sales_value'],
                    $stats['catalog_cost_value'],
                ],
            ];
        }

        $lowStock = Material::whereColumn('stock_quantity', '<=', 'min_stock')
            ->orderBy('stock_quantity')
            ->take(8)
            ->get();

        return $this->ok([
            'can_view_revenue' => $canRevenue,
            'stats' => $stats,
            'charts' => $charts,
            'low_stock' => MaterialResource::collection($lowStock),
            'permissions' => $user->permissions(),
            'role' => $user->role,
        ]);
    }

    private function countByMonth($query, string $dateColumn): array
    {
        return collect(range(5, 0))->map(function ($i) use ($query, $dateColumn) {
            $month = now()->subMonths($i);

            return (clone $query)
                ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->values()->all();
    }

    private function sumByMonth($query, string $sumColumn, string $dateColumn): array
    {
        return collect(range(5, 0))->map(function ($i) use ($query, $sumColumn, $dateColumn) {
            $month = now()->subMonths($i);

            return (float) (clone $query)
                ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum($sumColumn);
        })->values()->all();
    }
}
