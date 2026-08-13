<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\MaterialInput;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\SiteSetting;
use App\Services\ProductSaleService;
use App\Support\ProductQrCode;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SalesController extends ApiController
{
    public function __construct(private ProductSaleService $sales)
    {
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $product = $this->sales->findByScan($data['code']);
        if (! $product) {
            return $this->fail('Không tìm thấy sản phẩm.', 404);
        }

        ProductQrCode::ensureImage($product);

        return $this->ok([
            'product' => $this->productPayload($product, $request),
        ]);
    }

    public function sell(Request $request): JsonResponse
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
            return $this->fail($e->getMessage(), 422);
        }

        $sale->load(['product', 'seller']);

        return $this->ok([
            'sale' => $this->salePayload($sale, $request),
            'product' => $this->productPayload($sale->product->fresh(), $request),
            'print' => $sale->needs_shipping ? $this->printPayload($sale) : null,
        ], 'Đã ghi nhận bán hàng.');
    }

    public function history(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $query = ProductSale::with(['product:id,name,sku,image', 'seller:id,name'])->latest('sold_at');

        if ($q = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($q) {
                $builder->where('sale_code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%")
                    ->orWhere('receiver_name', 'like', "%{$q}%")
                    ->orWhere('receiver_phone', 'like', "%{$q}%")
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

        $canRevenue = $request->user()->canViewRevenue();

        $paginator = $query->paginate($perPage)->through(function (ProductSale $sale) use ($canRevenue) {
            return [
                'id' => $sale->id,
                'sale_code' => $sale->sale_code,
                'product' => $sale->product ? [
                    'id' => $sale->product->id,
                    'name' => $sale->product->name,
                    'sku' => $sale->product->sku,
                ] : null,
                'quantity' => $sale->quantity,
                'unit_price' => (float) $sale->unit_price,
                'total_price' => (float) $sale->total_price,
                'total_cost' => $canRevenue ? (float) $sale->total_cost : null,
                'profit' => $canRevenue ? (float) $sale->profit : null,
                'stock_after' => $sale->stock_after,
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'customer_source' => $sale->customer_source,
                'source_label' => $sale->source_label,
                'needs_shipping' => (bool) $sale->needs_shipping,
                'payment_method' => $sale->payment_method,
                'seller' => $sale->seller?->only(['id', 'name']),
                'sold_at' => optional($sale->sold_at)->toIso8601String(),
            ];
        });

        return $this->ok($paginator);
    }

    public function show(Request $request, ProductSale $sale): JsonResponse
    {
        $sale->load(['product', 'seller']);

        return $this->ok([
            'sale' => $this->salePayload($sale, $request, true),
            'print' => $sale->needs_shipping ? $this->printPayload($sale) : null,
        ]);
    }

    /**
     * Dữ liệu phiếu gửi hàng (app in / hiển thị).
     */
    public function printData(ProductSale $sale): JsonResponse
    {
        $sale->load(['product', 'seller']);

        return $this->ok($this->printPayload($sale));
    }

    public function report(Request $request): JsonResponse
    {
        if (! $request->user()->canViewRevenue()) {
            return $this->fail('Không có quyền xem doanh thu.', 403);
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

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

        return $this->ok([
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'sales' => $salesSummary,
            'material_spend' => $materialSpend,
            'operating_profit' => $salesSummary['gross_profit'] - $materialSpend,
            'top_products' => ProductSale::query()
                ->select('product_id')
                ->selectRaw('SUM(quantity) as units')
                ->selectRaw('SUM(total_price) as revenue')
                ->selectRaw('SUM(profit) as profit')
                ->whereBetween('sold_at', [$from, $to])
                ->groupBy('product_id')
                ->orderByDesc('revenue')
                ->with('product:id,name,sku')
                ->limit(10)
                ->get(),
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
        ]);
    }

    private function productPayload(Product $product, Request $request): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;
        $label = ProductQrCode::labelData($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'qr_token' => $product->qr_token,
            'qr_payload' => $label['qr_payload'],
            'qr_image_url' => $label['qr_image_url'],
            'label_image_url' => $label['label_image_url'],
            'print' => $label['print'],
            'stock' => (int) $product->stock,
            'price' => (float) $product->price,
            'final_price' => (float) $product->final_price,
            'price_formatted' => $label['price_formatted'],
            'is_on_sale' => (bool) $product->is_on_sale,
            'cost_price' => $canRevenue ? (float) $product->cost_price : null,
            'image_url' => $product->image_url,
        ];
    }

    private function salePayload(ProductSale $sale, Request $request, bool $full = false): array
    {
        $canRevenue = $request->user()?->canViewRevenue() ?? false;

        $base = [
            'id' => $sale->id,
            'sale_code' => $sale->sale_code,
            'product_id' => $sale->product_id,
            'product' => $sale->product ? [
                'id' => $sale->product->id,
                'name' => $sale->product->name,
                'sku' => $sale->product->sku,
            ] : null,
            'quantity' => $sale->quantity,
            'unit_price' => (float) $sale->unit_price,
            'total_price' => (float) $sale->total_price,
            'total_cost' => $canRevenue ? (float) $sale->total_cost : null,
            'profit' => $canRevenue ? (float) $sale->profit : null,
            'stock_before' => $sale->stock_before,
            'stock_after' => $sale->stock_after,
            'customer_name' => $sale->customer_name,
            'customer_phone' => $sale->customer_phone,
            'customer_email' => $sale->customer_email,
            'customer_address' => $sale->customer_address,
            'customer_ward' => $sale->customer_ward,
            'customer_district' => $sale->customer_district,
            'customer_province' => $sale->customer_province,
            'customer_postal_code' => $sale->customer_postal_code,
            'customer_full_address' => $sale->customer_full_address,
            'customer_source' => $sale->customer_source,
            'source_label' => $sale->source_label,
            'needs_shipping' => (bool) $sale->needs_shipping,
            'receiver_name' => $sale->receiver_name,
            'receiver_phone' => $sale->receiver_phone,
            'receiver_address' => $sale->receiver_address,
            'receiver_ward' => $sale->receiver_ward,
            'receiver_district' => $sale->receiver_district,
            'receiver_province' => $sale->receiver_province,
            'receiver_postal_code' => $sale->receiver_postal_code,
            'receiver_full_address' => $sale->effective_receiver_full_address,
            'shipping_note' => $sale->shipping_note,
            'carrier' => $sale->carrier,
            'carrier_label' => $sale->carrier_label,
            'shipping_service' => $sale->shipping_service,
            'service_label' => $sale->service_label,
            'payment_method' => $sale->payment_method,
            'payment_label' => $sale->payment_label,
            'cod_amount' => $sale->cod_amount !== null ? (float) $sale->cod_amount : null,
            'package_weight' => $sale->package_weight,
            'package_count' => $sale->package_count,
            'declared_value' => $sale->declared_value !== null ? (float) $sale->declared_value : null,
            'goods_content' => $sale->goods_content,
            'note' => $sale->note,
            'sold_at' => optional($sale->sold_at)->toIso8601String(),
            'seller' => $sale->seller?->only(['id', 'name']),
        ];

        if ($full) {
            $base['channel'] = $sale->channel;
            $base['scan_payload'] = $sale->scan_payload;
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function printPayload(ProductSale $sale): array
    {
        $settings = SiteSetting::allCached();
        $senderName = $settings['site_name'] ?? config('app.name', 'Cửa hàng');
        $senderPhone = $settings['hotline'] ?? ($settings['phone'] ?? null);
        $senderAddress = $settings['address'] ?? null;

        return [
            'sale_code' => $sale->sale_code,
            'sold_at' => optional($sale->sold_at)->toIso8601String(),
            'print_url' => url('/admin/sales/'.$sale->id.'/print'),
            'carrier' => $sale->carrier,
            'carrier_label' => $sale->carrier_label,
            'shipping_service' => $sale->shipping_service,
            'service_label' => $sale->service_label,
            'sender' => [
                'name' => $senderName,
                'phone' => $senderPhone,
                'email' => $settings['email'] ?? null,
                'address' => $senderAddress,
            ],
            'receiver' => [
                'name' => $sale->effective_receiver_name,
                'phone' => $sale->effective_receiver_phone,
                'address_line' => $sale->effective_receiver_address,
                'ward' => $sale->effective_receiver_ward,
                'district' => $sale->effective_receiver_district,
                'province' => $sale->effective_receiver_province,
                'postal_code' => $sale->effective_receiver_postal_code,
                'full_address' => $sale->effective_receiver_full_address,
            ],
            'goods' => [
                'product_name' => $sale->product?->name,
                'sku' => $sale->product?->sku,
                'content' => $sale->goods_content ?: $sale->product?->name,
                'quantity' => $sale->quantity,
                'unit_price' => (float) $sale->unit_price,
                'total_price' => (float) $sale->total_price,
                'package_weight' => $sale->package_weight,
                'package_count' => (int) ($sale->package_count ?: 1),
                'declared_value' => $sale->declared_value !== null ? (float) $sale->declared_value : null,
                'weight_from_product' => $sale->product?->weight_grams
                    ? (int) round((float) $sale->product->weight_grams * $sale->quantity)
                    : null,
            ],
            'payment' => [
                'method' => $sale->payment_method,
                'label' => $sale->payment_label,
                'cod_amount' => $sale->cod_amount !== null ? (float) $sale->cod_amount : null,
            ],
            'shipping_note' => $sale->shipping_note,
            'customer_source' => $sale->customer_source,
            'source_label' => $sale->source_label,
            'note' => $sale->note,
        ];
    }
}

