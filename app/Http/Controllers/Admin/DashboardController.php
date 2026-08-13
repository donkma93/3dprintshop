<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChatConversation;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Models\ProductSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
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
            $stats['inputs_total_30d'] = (float) MaterialInput::where('input_date', '>=', now()->subDays(30))
                ->sum('total_price');
            $stats['inputs_total_all'] = (float) MaterialInput::sum('total_price');

            $monthStart = now()->startOfMonth();
            $stats['qr_sales_revenue_month'] = (float) ProductSale::where('sold_at', '>=', $monthStart)->sum('total_price');
            $stats['qr_sales_profit_month'] = (float) ProductSale::where('sold_at', '>=', $monthStart)->sum('profit');
            $stats['qr_sales_units_month'] = (int) ProductSale::where('sold_at', '>=', $monthStart)->sum('quantity');
            $stats['qr_sales_count_month'] = (int) ProductSale::where('sold_at', '>=', $monthStart)->count();
        }

        $charts = $this->buildCharts($canRevenue);

        $lowStock = Material::whereColumn('stock_quantity', '<=', 'min_stock')
            ->orderBy('stock_quantity')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'charts', 'lowStock', 'canRevenue'));
    }

    private function buildCharts(bool $canRevenue): array
    {
        $driver = DB::connection()->getDriverName();
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $productsByMonth = $this->countByMonth(Product::query(), $months, $driver);
        $inputsByMonth = $this->countByMonth(MaterialInput::query(), $months, $driver);

        $charts = [
            'months' => $months->map(fn (Carbon $m) => $m->translatedFormat('m/Y'))->values()->all(),
            'products_created' => $productsByMonth,
            'inputs_count' => $inputsByMonth,
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
            'top_materials' => Material::orderByDesc('stock_quantity')->take(8)->get(['name', 'stock_quantity', 'unit'])
                ->map(fn ($m) => [
                    'name' => $m->name,
                    'qty' => (float) $m->stock_quantity,
                    'unit' => $m->unit,
                ])->all(),
        ];

        if ($canRevenue) {
            $spendByMonth = $this->sumByMonth(MaterialInput::query(), 'total_price', $months, $driver, 'input_date');
            $charts['inputs_spend'] = $spendByMonth;

            $charts['asset_breakdown'] = [
                'labels' => ['Giá trị tồn NL', 'Giá trị thiết bị', 'Giá trị SP (giá bán×kho)', 'Giá vốn SP×kho'],
                'values' => [
                    (float) Material::get()->sum(fn ($m) => $m->stock_value),
                    (float) Equipment::sum('purchase_price'),
                    (float) Product::where('is_active', true)->get()->sum(fn ($p) => (float) $p->price * (int) $p->stock),
                    (float) Product::where('is_active', true)->get()->sum(fn ($p) => (float) $p->cost_price * (int) $p->stock),
                ],
            ];
        }

        return $charts;
    }

    private function countByMonth($query, $months, string $driver, string $dateColumn = 'created_at'): array
    {
        return $months->map(function (Carbon $month) use ($query, $dateColumn) {
            return (clone $query)
                ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->count();
        })->values()->all();
    }

    private function sumByMonth($query, string $sumColumn, $months, string $driver, string $dateColumn = 'created_at'): array
    {
        return $months->map(function (Carbon $month) use ($query, $sumColumn, $dateColumn) {
            return (float) (clone $query)
                ->whereBetween($dateColumn, [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum($sumColumn);
        })->values()->all();
    }
}
