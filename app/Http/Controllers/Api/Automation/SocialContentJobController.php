<?php

namespace App\Http\Controllers\Api\Automation;

use App\Http\Controllers\Api\ApiController;
use App\Models\Product;
use App\Models\SocialContentJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SocialContentJobController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(SocialContentJob::STATUSES)],
            'approval_status' => ['nullable', Rule::in(SocialContentJob::APPROVAL_STATUSES)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $jobs = SocialContentJob::query()
            ->with('product')
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['approval_status'] ?? null, fn ($query, $status) => $query->where('approval_status', $status))
            ->oldest('updated_at')
            ->limit((int) ($data['limit'] ?? 25))
            ->get()
            ->map(fn (SocialContentJob $job) => $this->serialize($job));

        return $this->ok($jobs);
    }

    public function intake(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_driver' => ['nullable', 'string', 'max:40'],
            'source_file_id' => ['required', 'string', 'max:255'],
            'source_file_name' => ['required', 'string', 'max:255'],
            'source_mime_type' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'url', 'max:5000'],
            'source_hash' => ['nullable', 'string', 'max:64'],
            'sku' => ['nullable', 'string', 'max:100'],
        ]);

        $driver = $data['source_driver'] ?? 'google_drive';
        $sku = $this->resolveSku($data['sku'] ?? null, $data['source_file_name']);
        $product = $sku !== null
            ? Product::query()->with('category')->where('sku', Product::normalizeSku($sku))->first()
            : null;

        $job = SocialContentJob::firstOrCreate([
            'source_driver' => $driver,
            'source_file_id' => $data['source_file_id'],
        ], [
            'job_key' => (string) Str::uuid(),
            'product_id' => $product?->id,
            'source_file_name' => $data['source_file_name'],
            'source_mime_type' => $data['source_mime_type'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'source_hash' => $data['source_hash'] ?? null,
            'status' => $product ? 'validated' : 'needs_product',
            'product_snapshot' => $product ? $this->productSnapshot($product) : [
                'requested_sku' => $sku,
                'warning' => 'Không tìm thấy sản phẩm. Đặt tên file theo mẫu SKU__ten-anh.jpg hoặc truyền trường sku.',
            ],
        ]);

        if (! $job->wasRecentlyCreated) {
            return $this->ok([
                'duplicate' => true,
                'job' => $this->serialize($job->load('product')),
            ], 'File này đã được tiếp nhận trước đó.');
        }

        return $this->created([
            'duplicate' => false,
            'job' => $this->serialize($job->load('product')),
        ], $product ? 'Đã tạo content job.' : 'Đã tạo job nhưng chưa ghép được sản phẩm.');
    }

    public function show(SocialContentJob $socialContentJob): JsonResponse
    {
        return $this->ok($this->serialize($socialContentJob->load('product')));
    }

    public function update(Request $request, SocialContentJob $socialContentJob): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(SocialContentJob::STATUSES)],
            'generated_content' => ['nullable', 'array'],
            'media' => ['nullable', 'array'],
            'publishing' => ['nullable', 'array'],
            'last_error' => ['nullable', 'string', 'max:10000'],
            'increment_attempts' => ['nullable', 'boolean'],
        ]);

        foreach (['media', 'publishing'] as $jsonField) {
            if (array_key_exists($jsonField, $data)) {
                $data[$jsonField] = array_replace_recursive(
                    $socialContentJob->{$jsonField} ?? [],
                    $data[$jsonField] ?? []
                );
            }
        }

        if ($request->boolean('increment_attempts')) {
            $data['attempts'] = $socialContentJob->attempts + 1;
        }
        unset($data['increment_attempts']);

        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = now();
        }

        $socialContentJob->update($data);

        return $this->ok($this->serialize($socialContentJob->fresh('product')), 'Đã cập nhật content job.');
    }

    public function approve(Request $request, SocialContentJob $socialContentJob): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $approved = $data['decision'] === 'approved';
        $socialContentJob->update([
            'approval_status' => $data['decision'],
            'approval_note' => $data['note'] ?? null,
            'approved_at' => $approved ? now() : null,
            'status' => $approved ? 'approved' : 'rejected',
        ]);

        return $this->ok($this->serialize($socialContentJob->fresh('product')), $approved
            ? 'Đã duyệt nội dung.'
            : 'Đã từ chối nội dung.');
    }

    private function resolveSku(?string $sku, string $fileName): ?string
    {
        if (is_string($sku) && trim($sku) !== '') {
            return Product::normalizeSku($sku);
        }

        $stem = pathinfo($fileName, PATHINFO_FILENAME);
        $candidate = Str::contains($stem, '__') ? Str::before($stem, '__') : $stem;
        $candidate = Product::normalizeSku($candidate);

        return $candidate !== '' ? $candidate : null;
    }

    /** @return array<string, mixed> */
    private function productSnapshot(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => $product->category?->name,
            'short_description' => $product->short_description,
            'description' => trim(strip_tags((string) $product->description)),
            'price' => (float) $product->price,
            'final_price' => (float) $product->final_price,
            'price_formatted' => number_format((float) $product->final_price, 0, ',', '.').' đ',
            'is_on_sale' => (bool) $product->is_on_sale,
            'discount_percent' => (int) $product->discount_percent,
            'stock' => (int) $product->stock,
            'material_used' => $product->material_used,
            'weight_grams' => $product->weight_grams !== null ? (float) $product->weight_grams : null,
            'product_url' => $product->slug ? route('shop.products.show', $product->slug) : null,
            'image_url' => $product->image_url,
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(SocialContentJob $job): array
    {
        return Arr::only($job->toArray(), [
            'job_key',
            'product_id',
            'source_driver',
            'source_file_id',
            'source_file_name',
            'source_mime_type',
            'source_url',
            'source_hash',
            'status',
            'approval_status',
            'product_snapshot',
            'generated_content',
            'media',
            'publishing',
            'approval_note',
            'last_error',
            'attempts',
            'approved_at',
            'published_at',
            'created_at',
            'updated_at',
        ]);
    }
}
