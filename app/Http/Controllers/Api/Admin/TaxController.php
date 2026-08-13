<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\TaxLedgerEntry;
use App\Models\TaxPeriod;
use App\Models\TaxProfile;
use App\Services\TaxPreparationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TaxController extends ApiController
{
    public function __construct(private TaxPreparationService $tax)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $summary = $this->tax->summarize($request->query('period'));
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok($this->summaryPayload($summary));
    }

    public function periods(): JsonResponse
    {
        return $this->ok([
            'options' => $this->tax->recentPeriodOptions(12),
            'closed' => TaxPeriod::query()
                ->orderByDesc('ends_on')
                ->limit(20)
                ->get()
                ->map(fn (TaxPeriod $p) => $this->periodPayload($p)),
        ]);
    }

    public function profile(): JsonResponse
    {
        $profile = $this->tax->profile();

        return $this->ok([
            'profile' => $this->profilePayload($profile),
            'method_options' => TaxProfile::methodOptions(),
            'cycle_options' => TaxProfile::cycleOptions(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $profile = $this->tax->profile();

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:20'],
            'id_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
            'ward' => ['nullable', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'business_line' => ['nullable', 'string', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'method' => ['required', Rule::in(array_keys(TaxProfile::methodOptions()))],
            'filing_cycle' => ['required', Rule::in(array_keys(TaxProfile::cycleOptions()))],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'pit_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'revenue_threshold' => ['nullable', 'numeric', 'min:0'],
            'filing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'filing_month_offset' => ['required', 'integer', 'min:0', 'max:6'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'disclaimer' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile->fill($data);
        $profile->is_active = true;
        $profile->save();

        return $this->ok(['profile' => $this->profilePayload($profile->fresh())], 'Đã lưu hồ sơ thuế.');
    }

    public function ledger(Request $request): JsonResponse
    {
        try {
            $period = $this->tax->resolvePeriodRange($request->query('period'));
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 30)));
        $query = TaxLedgerEntry::query()
            ->with('creator:id,name')
            ->whereDate('entry_date', '>=', $period['starts']->toDateString())
            ->whereDate('entry_date', '<=', $period['ends']->toDateString())
            ->latest('entry_date')
            ->latest('id');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('description', 'like', "%{$q}%")
                    ->orWhere('entry_code', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%");
            });
        }

        if ($source = $request->query('source')) {
            $query->where('source_type', $source);
        }

        $entries = $query->paginate($perPage);

        return $this->ok([
            'period' => [
                'key' => $period['key'],
                'starts_on' => $period['starts']->toDateString(),
                'ends_on' => $period['ends']->toDateString(),
                'due_on' => $period['due_on']->toDateString(),
            ],
            'entries' => $entries->getCollection()->map(fn (TaxLedgerEntry $e) => $this->entryPayload($e))->values(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function storeEntry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'source_type' => ['required', Rule::in([TaxLedgerEntry::SOURCE_MANUAL, TaxLedgerEntry::SOURCE_ADJUSTMENT])],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric'],
            'tax_group' => ['required', Rule::in(array_keys(TaxLedgerEntry::groupOptions()))],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'invoice_status' => ['nullable', Rule::in(array_keys(TaxLedgerEntry::invoiceOptions()))],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'is_excluded' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $data['is_excluded'] = $request->boolean('is_excluded');

        try {
            $entry = $this->tax->addManualEntry($data, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok(['entry' => $this->entryPayload($entry)], 'Đã ghi sổ.', 201);
    }

    public function updateEntry(Request $request, TaxLedgerEntry $entry): JsonResponse
    {
        if ($this->tax->isDateLocked($entry->entry_date->toDateString())) {
            return $this->fail('Không thể sửa: ngày thuộc kỳ đã khóa.', 422);
        }

        if ($entry->source_type === TaxLedgerEntry::SOURCE_PRODUCT_SALE) {
            $data = $request->validate([
                'invoice_status' => ['nullable', Rule::in(array_keys(TaxLedgerEntry::invoiceOptions()))],
                'invoice_number' => ['nullable', 'string', 'max:80'],
                'is_excluded' => ['nullable', 'boolean'],
                'note' => ['nullable', 'string', 'max:500'],
            ]);
            $entry->update([
                'invoice_status' => $data['invoice_status'] ?? $entry->invoice_status,
                'invoice_number' => $data['invoice_number'] ?? $entry->invoice_number,
                'is_excluded' => $request->boolean('is_excluded'),
                'note' => $data['note'] ?? $entry->note,
            ]);

            return $this->ok(['entry' => $this->entryPayload($entry->fresh())], 'Đã cập nhật.');
        }

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric'],
            'tax_group' => ['required', Rule::in(array_keys(TaxLedgerEntry::groupOptions()))],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'invoice_status' => ['nullable', Rule::in(array_keys(TaxLedgerEntry::invoiceOptions()))],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'is_excluded' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->tax->isDateLocked($data['entry_date'])) {
            return $this->fail('Không thể chuyển sang ngày thuộc kỳ đã khóa.', 422);
        }

        $amount = (float) $data['amount'];
        if ($entry->source_type === TaxLedgerEntry::SOURCE_ADJUSTMENT && $amount > 0) {
            $amount = -abs($amount);
        }

        $entry->update([
            'entry_date' => $data['entry_date'],
            'description' => $data['description'],
            'amount' => $amount,
            'tax_group' => $data['tax_group'],
            'payment_method' => $data['payment_method'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'invoice_status' => $data['invoice_status'] ?? TaxLedgerEntry::INVOICE_NONE,
            'invoice_number' => $data['invoice_number'] ?? null,
            'is_excluded' => $request->boolean('is_excluded'),
            'note' => $data['note'] ?? null,
        ]);

        return $this->ok(['entry' => $this->entryPayload($entry->fresh())], 'Đã cập nhật.');
    }

    public function destroyEntry(TaxLedgerEntry $entry): JsonResponse
    {
        if ($entry->source_type === TaxLedgerEntry::SOURCE_PRODUCT_SALE) {
            return $this->fail('Không xóa dòng đồng bộ từ bán hàng.', 422);
        }
        if ($this->tax->isDateLocked($entry->entry_date->toDateString())) {
            return $this->fail('Không thể xóa: ngày thuộc kỳ đã khóa.', 422);
        }

        $entry->delete();

        return $this->ok(null, 'Đã xóa dòng sổ.');
    }

    public function sync(Request $request): JsonResponse
    {
        $from = $request->filled('from') ? \Carbon\Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? \Carbon\Carbon::parse($request->input('to'))->endOfDay() : null;
        $count = $this->tax->syncSalesToLedger($from, $to);

        return $this->ok(['synced' => $count], "Đã đồng bộ {$count} giao dịch.");
    }

    public function closePeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $row = $this->tax->closePeriod($data['period'], $request->user(), $data['admin_note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok(['period' => $this->periodPayload($row)], 'Đã khóa sổ.');
    }

    public function reopenPeriod(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
        ]);

        try {
            $row = $this->tax->reopenPeriod($data['period']);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok(['period' => $this->periodPayload($row)], 'Đã mở lại kỳ.');
    }

    public function markPaid(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'paid_on' => ['nullable', 'date'],
            'payment_ref' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $row = $this->tax->markPaid(
                $data['period'],
                (float) $data['paid_amount'],
                $data['paid_on'] ?? null,
                $data['payment_ref'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->ok(['period' => $this->periodPayload($row)], 'Đã ghi nhận nộp thuế.');
    }

    private function summaryPayload(array $summary): array
    {
        return [
            'period' => $summary['period'],
            'profile' => $this->profilePayload($summary['profile']),
            'revenue_gross' => $summary['revenue_gross'],
            'revenue_positive' => $summary['revenue_positive'],
            'adjustment_total' => $summary['adjustment_total'],
            'taxable_revenue' => $summary['taxable_revenue'],
            'estimated_vat' => $summary['estimated_vat'],
            'estimated_pit' => $summary['estimated_pit'],
            'estimated_total' => $summary['estimated_total'],
            'by_group' => $summary['by_group'],
            'by_day' => $summary['by_day']->map(fn ($r) => [
                'day' => $r->day,
                'total' => (float) $r->total,
                'entries' => (int) $r->entries,
            ])->values(),
            'ytd_revenue' => $summary['ytd_revenue'],
            'threshold' => $summary['threshold'],
            'threshold_ratio' => $summary['threshold_ratio'],
            'threshold_warning' => $summary['threshold_warning'],
            'entry_count' => $summary['entry_count'],
            'excluded_count' => $summary['excluded_count'],
            'locked' => $summary['locked'],
            'tax_period' => $summary['tax_period'] ? $this->periodPayload($summary['tax_period']) : null,
            'days_to_due' => $summary['days_to_due'],
            'disclaimer' => $summary['profile']->disclaimer,
        ];
    }

    private function profilePayload(TaxProfile $p): array
    {
        return [
            'id' => $p->id,
            'business_name' => $p->business_name,
            'owner_name' => $p->owner_name,
            'tax_code' => $p->tax_code,
            'id_number' => $p->id_number,
            'phone' => $p->phone,
            'email' => $p->email,
            'address' => $p->address,
            'ward' => $p->ward,
            'district' => $p->district,
            'province' => $p->province,
            'full_address' => $p->full_address,
            'business_line' => $p->business_line,
            'tax_office' => $p->tax_office,
            'method' => $p->method,
            'method_label' => $p->method_label,
            'filing_cycle' => $p->filing_cycle,
            'cycle_label' => $p->cycle_label,
            'vat_rate' => (float) $p->vat_rate,
            'pit_rate' => (float) $p->pit_rate,
            'revenue_threshold' => $p->revenue_threshold !== null ? (float) $p->revenue_threshold : null,
            'filing_day' => (int) $p->filing_day,
            'filing_month_offset' => (int) $p->filing_month_offset,
            'notes' => $p->notes,
            'disclaimer' => $p->disclaimer,
            'is_active' => (bool) $p->is_active,
        ];
    }

    private function periodPayload(TaxPeriod $p): array
    {
        return [
            'id' => $p->id,
            'period_key' => $p->period_key,
            'period_type' => $p->period_type,
            'year' => $p->year,
            'month' => $p->month,
            'quarter' => $p->quarter,
            'starts_on' => optional($p->starts_on)->toDateString(),
            'ends_on' => optional($p->ends_on)->toDateString(),
            'due_on' => optional($p->due_on)->toDateString(),
            'status' => $p->status,
            'status_label' => $p->status_label,
            'revenue_total' => (float) $p->revenue_total,
            'adjustment_total' => (float) $p->adjustment_total,
            'taxable_revenue' => (float) $p->taxable_revenue,
            'estimated_vat' => (float) $p->estimated_vat,
            'estimated_pit' => (float) $p->estimated_pit,
            'estimated_total' => (float) $p->estimated_total,
            'paid_amount' => $p->paid_amount !== null ? (float) $p->paid_amount : null,
            'paid_on' => optional($p->paid_on)->toDateString(),
            'payment_ref' => $p->payment_ref,
            'admin_note' => $p->admin_note,
            'closed_at' => optional($p->closed_at)->toDateTimeString(),
            'locked' => $p->isClosed(),
        ];
    }

    private function entryPayload(TaxLedgerEntry $e): array
    {
        return [
            'id' => $e->id,
            'entry_code' => $e->entry_code,
            'entry_date' => optional($e->entry_date)->toDateString(),
            'source_type' => $e->source_type,
            'source_label' => $e->source_label,
            'source_id' => $e->source_id,
            'description' => $e->description,
            'amount' => (float) $e->amount,
            'tax_group' => $e->tax_group,
            'group_label' => $e->group_label,
            'payment_method' => $e->payment_method,
            'customer_name' => $e->customer_name,
            'customer_phone' => $e->customer_phone,
            'invoice_status' => $e->invoice_status,
            'invoice_label' => $e->invoice_label,
            'invoice_number' => $e->invoice_number,
            'is_excluded' => (bool) $e->is_excluded,
            'note' => $e->note,
            'created_by' => $e->created_by,
            'creator_name' => $e->creator?->name,
            'created_at' => optional($e->created_at)->toDateTimeString(),
        ];
    }
}
