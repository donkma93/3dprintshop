<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use App\Support\ProductQrCode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ProductSaleService
{
    public function findByScan(?string $raw): ?Product
    {
        $token = ProductQrCode::extractToken($raw);
        if (! $token) {
            return null;
        }

        $product = Product::where('qr_token', $token)->first();
        if ($product) {
            return $product;
        }

        return Product::where('sku', $token)->first();
    }

    /**
     * Ghi nhận bán nội bộ: giảm tồn, lưu doanh thu / giá vốn / lãi + thông tin KH / giao hàng.
     *
     * @param  array<string, mixed>  $customerData
     */
    public function sell(
        Product $product,
        int $quantity,
        ?User $seller = null,
        ?float $unitPrice = null,
        ?string $note = null,
        ?string $scanPayload = null,
        string $channel = 'qr_internal',
        array $customerData = []
    ): ProductSale {
        $quantity = max(1, $quantity);
        $customer = $this->normalizeCustomer($customerData);
        $shipping = $this->normalizeShipping($customerData, $customer);

        return DB::transaction(function () use (
            $product, $quantity, $seller, $unitPrice, $note, $scanPayload, $channel, $customer, $shipping
        ) {
            /** @var Product $locked */
            $locked = Product::whereKey($product->id)->lockForUpdate()->first();
            if (! $locked) {
                throw new RuntimeException('Không tìm thấy sản phẩm.');
            }

            $stockBefore = (int) $locked->stock;
            if ($stockBefore < $quantity) {
                throw new InvalidArgumentException(
                    $stockBefore <= 0
                        ? 'Sản phẩm đã hết hàng (tồn = 0).'
                        : "Không đủ tồn kho. Hiện còn {$stockBefore}, yêu cầu bán {$quantity}."
                );
            }

            $price = $unitPrice !== null ? (float) $unitPrice : (float) $locked->final_price;
            if ($price < 0) {
                throw new InvalidArgumentException('Giá bán không hợp lệ.');
            }

            $cost = (float) $locked->cost_price;
            $stockAfter = $stockBefore - $quantity;

            $locked->stock = $stockAfter;
            $locked->save();

            $total = round($price * $quantity, 2);

            if (
                ($shipping['payment_method'] ?? null) === ProductSale::PAYMENT_COD
                && $shipping['cod_amount'] === null
            ) {
                $shipping['cod_amount'] = $total;
            }

            if ($shipping['declared_value'] === null && ($shipping['needs_shipping'] ?? false)) {
                $shipping['declared_value'] = $total;
            }

            if (empty($shipping['goods_content']) && ($shipping['needs_shipping'] ?? false)) {
                $shipping['goods_content'] = $locked->name;
            }

            $sale = ProductSale::create(array_merge([
                'product_id' => $locked->id,
                'sold_by' => $seller?->id,
                'quantity' => $quantity,
                'unit_price' => $price,
                'unit_cost' => $cost,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'scan_payload' => $scanPayload,
                'channel' => $channel,
                'note' => $note,
                'sold_at' => now(),
            ], $customer, $shipping));

            // Đồng bộ nhẹ sang sổ thuế (module riêng; bỏ qua nếu kỳ khóa / lỗi)
            try {
                app(TaxPreparationService::class)->syncSalesToLedger(
                    now()->startOfDay(),
                    now()->endOfDay()
                );
            } catch (\Throwable) {
                // không chặn bán hàng
            }

            return $sale;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCustomer(array $data): array
    {
        return [
            'customer_name' => $this->str($data, 'customer_name'),
            'customer_phone' => $this->str($data, 'customer_phone'),
            'customer_email' => $this->str($data, 'customer_email'),
            'customer_address' => $this->str($data, 'customer_address'),
            'customer_ward' => $this->str($data, 'customer_ward'),
            'customer_district' => $this->str($data, 'customer_district'),
            'customer_province' => $this->str($data, 'customer_province'),
            'customer_postal_code' => $this->str($data, 'customer_postal_code'),
            'customer_source' => $this->str($data, 'customer_source'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function normalizeShipping(array $data, array $customer): array
    {
        $needs = false;
        if (array_key_exists('needs_shipping', $data)) {
            $raw = $data['needs_shipping'];
            $needs = in_array($raw, [1, '1', 'on', true, 'true'], true)
                || filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }

        $receiverName = $this->str($data, 'receiver_name');
        $receiverPhone = $this->str($data, 'receiver_phone');
        $receiverAddress = $this->str($data, 'receiver_address');
        $receiverWard = $this->str($data, 'receiver_ward');
        $receiverDistrict = $this->str($data, 'receiver_district');
        $receiverProvince = $this->str($data, 'receiver_province');
        $receiverPostal = $this->str($data, 'receiver_postal_code');

        $shippingNote = $this->str($data, 'shipping_note');
        $carrier = $this->str($data, 'carrier');
        $service = $this->str($data, 'shipping_service');
        $payment = $this->str($data, 'payment_method');
        $goodsContent = $this->str($data, 'goods_content');

        $codAmount = $this->floatOrNull($data, 'cod_amount');
        $declared = $this->floatOrNull($data, 'declared_value');
        $weight = $this->intOrNull($data, 'package_weight');
        $count = $this->intOrNull($data, 'package_count');
        if ($count !== null) {
            $count = max(1, min(99, $count));
        } else {
            $count = 1;
        }

        if ($needs) {
            $receiverName = $receiverName ?: ($customer['customer_name'] ?? null);
            $receiverPhone = $receiverPhone ?: ($customer['customer_phone'] ?? null);
            $receiverAddress = $receiverAddress ?: ($customer['customer_address'] ?? null);
            $receiverWard = $receiverWard ?: ($customer['customer_ward'] ?? null);
            $receiverDistrict = $receiverDistrict ?: ($customer['customer_district'] ?? null);
            $receiverProvince = $receiverProvince ?: ($customer['customer_province'] ?? null);
            $receiverPostal = $receiverPostal ?: ($customer['customer_postal_code'] ?? null);

            $full = ProductSale::composeAddress(
                $receiverAddress,
                $receiverWard,
                $receiverDistrict,
                $receiverProvince,
                $receiverPostal
            );

            if (! $receiverName || ! $receiverPhone || ! $full) {
                throw new InvalidArgumentException(
                    'Đơn gửi hàng cần đủ: họ tên, SĐT và địa chỉ giao (số nhà/đường + phường/xã hoặc quận/huyện hoặc tỉnh/TP).'
                );
            }

            // Cần ít nhất số nhà/đường hoặc (phường + tỉnh) để đủ gửi CPN
            if (! $receiverAddress && ! ($receiverWard || $receiverDistrict || $receiverProvince)) {
                throw new InvalidArgumentException(
                    'Vui lòng nhập địa chỉ giao hàng (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).'
                );
            }
        }

        return [
            'needs_shipping' => $needs,
            'receiver_name' => $receiverName,
            'receiver_phone' => $receiverPhone,
            'receiver_address' => $receiverAddress,
            'receiver_ward' => $receiverWard,
            'receiver_district' => $receiverDistrict,
            'receiver_province' => $receiverProvince,
            'receiver_postal_code' => $receiverPostal,
            'shipping_note' => $shippingNote,
            'carrier' => $carrier,
            'shipping_service' => $service,
            'payment_method' => $payment,
            'cod_amount' => $codAmount,
            'package_weight' => $weight,
            'package_count' => $count,
            'declared_value' => $declared,
            'goods_content' => $goodsContent,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function str(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }
        $v = trim((string) $data[$key]);

        return $v === '' ? null : $v;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function floatOrNull(array $data, string $key): ?float
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        return (float) $data[$key];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function intOrNull(array $data, string $key): ?int
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        return (int) $data[$key];
    }
}
