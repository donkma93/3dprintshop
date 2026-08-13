<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\SiteSetting;
use App\Services\ProductSaleService;
use App\Support\ProductQrCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SalesController extends Controller
{
    public function __construct(private ProductSaleService $sales)
    {
    }

    /**
     * Trang quét QR / bán nội bộ — mọi role có permission sales.sell.
     */
    public function scan(Request $request)
    {
        $product = null;
        $lookup = trim((string) $request->query('code', ''));
        $error = null;

        if ($lookup !== '') {
            $product = $this->sales->findByScan($lookup);
            if (! $product) {
                $error = 'Không tìm thấy sản phẩm với mã: '.$lookup;
            }
        }

        $recent = ProductSale::with(['product', 'seller'])
            ->latest('sold_at')
            ->limit(12)
            ->get();

        $sourceOptions = ProductSale::sourceOptions();
        $paymentOptions = ProductSale::paymentOptions();
        $carrierOptions = ProductSale::carrierOptions();
        $serviceOptions = ProductSale::serviceOptions();

        return view('admin.sales.scan', compact(
            'product',
            'lookup',
            'error',
            'recent',
            'sourceOptions',
            'paymentOptions',
            'carrierOptions',
            'serviceOptions'
        ));
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $product = $this->sales->findByScan($data['code']);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm.',
            ], 404);
        }

        ProductQrCode::ensureImage($product);

        return response()->json([
            'success' => true,
            'product' => $this->productPayload($product),
        ]);
    }

    public function sell(Request $request)
    {
        $data = $this->validateSale($request);

        $product = Product::findOrFail($data['product_id']);

        try {
            $sale = $this->sales->sell(
                $product,
                (int) $data['quantity'],
                $request->user(),
                array_key_exists('unit_price', $data) && $data['unit_price'] !== null
                    ? (float) $data['unit_price']
                    : null,
                $data['note'] ?? null,
                $data['scan_payload'] ?? null,
                'qr_internal',
                $data
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $sale->load(['product', 'seller']);
        $message = 'Đã bán '.$sale->quantity.' × '.$sale->product->name
            .' — tồn còn '.$sale->stock_after
            .' — doanh thu '.number_format((float) $sale->total_price, 0, ',', '.').' đ';
        if ($sale->customer_name) {
            $message .= ' — KH: '.$sale->customer_name;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sale' => $this->salePayload($sale),
                'product' => $this->productPayload($sale->product->fresh()),
                'print_url' => $sale->needs_shipping
                    ? route('admin.sales.print', $sale)
                    : null,
            ]);
        }

        if ($sale->needs_shipping) {
            return redirect()
                ->route('admin.sales.print', $sale)
                ->with('success', $message.' — In phiếu gửi hàng.');
        }

        return redirect()
            ->route('admin.sales.scan', ['code' => $product->qr_token])
            ->with('success', $message);
    }

    public function history(Request $request)
    {
        $query = ProductSale::with(['product', 'seller'])->latest('sold_at');

        if ($q = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('sale_code', 'like', "%{$q}%")
                    ->orWhere('scan_payload', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%")
                    ->orWhere('customer_address', 'like', "%{$q}%")
                    ->orWhere('customer_ward', 'like', "%{$q}%")
                    ->orWhere('customer_district', 'like', "%{$q}%")
                    ->orWhere('customer_province', 'like', "%{$q}%")
                    ->orWhere('receiver_name', 'like', "%{$q}%")
                    ->orWhere('receiver_phone', 'like', "%{$q}%")
                    ->orWhere('receiver_address', 'like', "%{$q}%")
                    ->orWhere('receiver_ward', 'like', "%{$q}%")
                    ->orWhere('receiver_district', 'like', "%{$q}%")
                    ->orWhere('receiver_province', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($p) use ($q) {
                        $p->where('name', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%")
                            ->orWhere('qr_token', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('sold_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('sold_at', '<=', $request->date('to'));
        }
        if ($request->boolean('shipping_only')) {
            $query->where('needs_shipping', true);
        }

        $sales = $query->paginate(20)->withQueryString();
        $canRevenue = $request->user()->canViewRevenue();

        return view('admin.sales.history', compact('sales', 'canRevenue'));
    }

    /**
     * Phiếu gửi hàng in dán kiện — người gửi từ SiteSetting, người nhận + hàng từ sale.
     */
    public function printSlip(ProductSale $sale)
    {
        $sale->load(['product', 'seller']);
        $settings = SiteSetting::allCached();
        $sender = [
            'name' => $settings['site_name'] ?? config('app.name', 'Cửa hàng'),
            'phone' => $settings['hotline'] ?? ($settings['phone'] ?? null),
            'email' => $settings['email'] ?? null,
            'address' => $settings['address'] ?? null,
        ];

        return view('admin.sales.print', compact('sale', 'sender', 'settings'));
    }

    /**
     * Báo cáo lãi/lỗ: doanh thu SP bán + chi phí nhập nguyên liệu.
     */
    public function report(Request $request)
    {
        if (! $request->user()->canViewRevenue()) {
            abort(403, 'Chỉ quản trị viên được xem báo cáo doanh thu / lãi lỗ.');
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $salesQuery = ProductSale::query()->whereBetween('sold_at', [$from, $to]);
        $salesSummary = [
            'orders' => (clone $salesQuery)->count(),
            'units' => (int) (clone $salesQuery)->sum('quantity'),
            'revenue' => (float) (clone $salesQuery)->sum('total_price'),
            'cogs' => (float) (clone $salesQuery)->sum('total_cost'),
            'gross_profit' => (float) (clone $salesQuery)->sum('profit'),
        ];

        $materialSpend = (float) MaterialInput::query()
            ->whereBetween('input_date', [$from->toDateString(), $to->toDateString()])
            ->sum('total_price');

        // Lãi/lỗ vận hành kỳ: lãi gộp bán SP − chi nhập NL trong kỳ
        $operatingProfit = $salesSummary['gross_profit'] - $materialSpend;

        $byDay = ProductSale::query()
            ->selectRaw('DATE(sold_at) as day')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(quantity) as units')
            ->selectRaw('SUM(total_price) as revenue')
            ->selectRaw('SUM(total_cost) as cogs')
            ->selectRaw('SUM(profit) as profit')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('day')
            ->get();

        $materialByDay = MaterialInput::query()
            ->selectRaw('DATE(input_date) as day')
            ->selectRaw('SUM(total_price) as spend')
            ->whereBetween('input_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy(DB::raw('DATE(input_date)'))
            ->orderBy('day')
            ->pluck('spend', 'day');

        $topProducts = ProductSale::query()
            ->select('product_id')
            ->selectRaw('SUM(quantity) as units')
            ->selectRaw('SUM(total_price) as revenue')
            ->selectRaw('SUM(profit) as profit')
            ->whereBetween('sold_at', [$from, $to])
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->with('product')
            ->limit(10)
            ->get();

        $recentSales = ProductSale::with(['product', 'seller'])
            ->whereBetween('sold_at', [$from, $to])
            ->latest('sold_at')
            ->limit(15)
            ->get();

        $materialInputs = MaterialInput::with('material')
            ->whereBetween('input_date', [$from->toDateString(), $to->toDateString()])
            ->latest('input_date')
            ->limit(15)
            ->get();

        return view('admin.sales.report', [
            'from' => $from,
            'to' => $to,
            'salesSummary' => $salesSummary,
            'materialSpend' => $materialSpend,
            'operatingProfit' => $operatingProfit,
            'byDay' => $byDay,
            'materialByDay' => $materialByDay,
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'materialInputs' => $materialInputs,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSale(Request $request): array
    {
        $sources = array_keys(ProductSale::sourceOptions());
        $payments = array_keys(ProductSale::paymentOptions());
        $carriers = array_keys(ProductSale::carrierOptions());
        $services = array_keys(ProductSale::serviceOptions());

        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'scan_payload' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'customer_ward' => ['nullable', 'string', 'max:120'],
            'customer_district' => ['nullable', 'string', 'max:120'],
            'customer_province' => ['nullable', 'string', 'max:120'],
            'customer_postal_code' => ['nullable', 'string', 'max:20'],
            'customer_source' => ['nullable', Rule::in($sources)],
            'needs_shipping' => ['nullable', 'boolean'],
            'receiver_name' => ['nullable', 'string', 'max:120'],
            'receiver_phone' => ['nullable', 'string', 'max:40'],
            'receiver_address' => ['nullable', 'string', 'max:500'],
            'receiver_ward' => ['nullable', 'string', 'max:120'],
            'receiver_district' => ['nullable', 'string', 'max:120'],
            'receiver_province' => ['nullable', 'string', 'max:120'],
            'receiver_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_note' => ['nullable', 'string', 'max:500'],
            'carrier' => ['nullable', Rule::in($carriers)],
            'shipping_service' => ['nullable', Rule::in($services)],
            'payment_method' => ['nullable', Rule::in($payments)],
            'cod_amount' => ['nullable', 'numeric', 'min:0'],
            'package_weight' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'package_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'goods_content' => ['nullable', 'string', 'max:255'],
        ], [
            'quantity.min' => 'Số lượng bán tối thiểu là 1.',
        ]);
    }

    private function productPayload(Product $product): array
    {
        $canRevenue = auth()->user()?->canViewRevenue() ?? false;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'qr_token' => $product->qr_token,
            'qr_payload' => ProductQrCode::payload($product),
            'stock' => (int) $product->stock,
            'price' => (float) $product->price,
            'final_price' => (float) $product->final_price,
            'is_on_sale' => (bool) $product->is_on_sale,
            'cost_price' => $canRevenue ? (float) $product->cost_price : null,
            'image_url' => $product->image_url,
            'category' => $product->category?->name,
        ];
    }

    private function salePayload(ProductSale $sale): array
    {
        return [
            'id' => $sale->id,
            'sale_code' => $sale->sale_code,
            'quantity' => $sale->quantity,
            'unit_price' => (float) $sale->unit_price,
            'total_price' => (float) $sale->total_price,
            'total_cost' => (float) $sale->total_cost,
            'profit' => (float) $sale->profit,
            'stock_after' => $sale->stock_after,
            'customer_name' => $sale->customer_name,
            'customer_phone' => $sale->customer_phone,
            'customer_full_address' => $sale->customer_full_address,
            'customer_source' => $sale->customer_source,
            'needs_shipping' => (bool) $sale->needs_shipping,
            'receiver_name' => $sale->effective_receiver_name,
            'receiver_phone' => $sale->effective_receiver_phone,
            'receiver_full_address' => $sale->effective_receiver_full_address,
            'payment_method' => $sale->payment_method,
            'cod_amount' => $sale->cod_amount !== null ? (float) $sale->cod_amount : null,
            'carrier' => $sale->carrier,
            'sold_at' => optional($sale->sold_at)->toIso8601String(),
        ];
    }
}
