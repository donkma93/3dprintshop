<?php

namespace App\Services;

use App\Models\ProductSale;
use App\Models\TaxLedgerEntry;
use App\Models\TaxPeriod;
use App\Models\TaxProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaxPreparationService
{
    /**
     * Hồ sơ thuế đang dùng (tạo mặc định nếu chưa có).
     */
    public function profile(): TaxProfile
    {
        $profile = TaxProfile::active();
        if ($profile) {
            return $profile;
        }

        return TaxProfile::create([
            'business_name' => 'Hộ kinh doanh',
            'method' => TaxProfile::METHOD_PRESUMPTIVE,
            'filing_cycle' => TaxProfile::CYCLE_QUARTER,
            'vat_rate' => 1,
            'pit_rate' => 0.5,
            'revenue_threshold' => 100000000,
            'filing_day' => 30,
            'filing_month_offset' => 1,
            'is_active' => true,
            'disclaimer' => 'Số liệu chỉ phục vụ chuẩn bị / quản trị nội bộ. Không thay thế tờ khai và quyết định của cơ quan thuế. Cập nhật tỷ lệ theo văn bản hiện hành.',
        ]);
    }

    /**
     * Đồng bộ product_sales → sổ thuế (bỏ qua đơn đã có).
     */
    public function syncSalesToLedger(?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = ProductSale::query()->with('product:id,name')->orderBy('id');
        if ($from) {
            $query->whereDate('sold_at', '>=', $from->toDateString());
        }
        if ($to) {
            $query->whereDate('sold_at', '<=', $to->toDateString());
        }

        $synced = 0;
        $query->chunkById(100, function ($sales) use (&$synced) {
            foreach ($sales as $sale) {
                $exists = TaxLedgerEntry::where('source_type', TaxLedgerEntry::SOURCE_PRODUCT_SALE)
                    ->where('source_id', $sale->id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                // Không đồng bộ vào kỳ đã khóa
                $date = Carbon::parse($sale->sold_at)->toDateString();
                if ($this->isDateLocked($date)) {
                    continue;
                }

                TaxLedgerEntry::create([
                    'entry_date' => $date,
                    'source_type' => TaxLedgerEntry::SOURCE_PRODUCT_SALE,
                    'source_id' => $sale->id,
                    'description' => 'Bán: '.($sale->product?->name ?? ('#'.$sale->id))
                        .' × '.$sale->quantity
                        .' ('.$sale->sale_code.')',
                    'amount' => (float) $sale->total_price,
                    'tax_group' => TaxLedgerEntry::GROUP_COMMERCE,
                    'payment_method' => $sale->payment_method,
                    'customer_name' => $sale->customer_name,
                    'customer_phone' => $sale->customer_phone,
                    'invoice_status' => TaxLedgerEntry::INVOICE_NONE,
                    'note' => $sale->note,
                    'created_by' => $sale->sold_by,
                ]);
                $synced++;
            }
        });

        return $synced;
    }

    public function isDateLocked(string $date): bool
    {
        return TaxPeriod::query()
            ->where('status', TaxPeriod::STATUS_CLOSED)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->exists();
    }

    /**
     * @return array{starts: Carbon, ends: Carbon, key: string, type: string, year: int, month: ?int, quarter: ?int, due_on: Carbon}
     */
    public function resolvePeriodRange(?string $periodKey = null, ?TaxProfile $profile = null): array
    {
        $profile = $profile ?? $this->profile();
        $cycle = $profile->filing_cycle ?: TaxProfile::CYCLE_QUARTER;
        $now = now();

        if ($periodKey) {
            return $this->parsePeriodKey($periodKey, $profile);
        }

        if ($cycle === TaxProfile::CYCLE_MONTH) {
            $starts = $now->copy()->startOfMonth();
            $ends = $now->copy()->endOfMonth();
            $key = $starts->format('Y-m');

            return $this->buildRange($key, TaxProfile::CYCLE_MONTH, $starts, $ends, $profile, (int) $starts->year, (int) $starts->month, null);
        }

        if ($cycle === TaxProfile::CYCLE_YEAR) {
            $starts = $now->copy()->startOfYear();
            $ends = $now->copy()->endOfYear();
            $key = (string) $starts->year;

            return $this->buildRange($key, TaxProfile::CYCLE_YEAR, $starts, $ends, $profile, (int) $starts->year, null, null);
        }

        // quarter
        $q = (int) ceil($now->month / 3);
        $starts = Carbon::create($now->year, ($q - 1) * 3 + 1, 1)->startOfDay();
        $ends = $starts->copy()->addMonths(2)->endOfMonth();
        $key = $now->year.'-Q'.$q;

        return $this->buildRange($key, TaxProfile::CYCLE_QUARTER, $starts, $ends, $profile, (int) $now->year, null, $q);
    }

    /**
     * @return array{starts: Carbon, ends: Carbon, key: string, type: string, year: int, month: ?int, quarter: ?int, due_on: Carbon}
     */
    public function parsePeriodKey(string $key, ?TaxProfile $profile = null): array
    {
        $profile = $profile ?? $this->profile();
        $key = trim($key);

        if (preg_match('/^(\d{4})-Q([1-4])$/i', $key, $m)) {
            $year = (int) $m[1];
            $q = (int) $m[2];
            $starts = Carbon::create($year, ($q - 1) * 3 + 1, 1)->startOfDay();
            $ends = $starts->copy()->addMonths(2)->endOfMonth();

            return $this->buildRange(strtoupper($key), TaxProfile::CYCLE_QUARTER, $starts, $ends, $profile, $year, null, $q);
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $key, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $starts = Carbon::create($year, $month, 1)->startOfDay();
            $ends = $starts->copy()->endOfMonth();

            return $this->buildRange(sprintf('%04d-%02d', $year, $month), TaxProfile::CYCLE_MONTH, $starts, $ends, $profile, $year, $month, null);
        }

        if (preg_match('/^(\d{4})$/', $key, $m)) {
            $year = (int) $m[1];
            $starts = Carbon::create($year, 1, 1)->startOfDay();
            $ends = Carbon::create($year, 12, 31)->endOfDay();

            return $this->buildRange((string) $year, TaxProfile::CYCLE_YEAR, $starts, $ends, $profile, $year, null, null);
        }

        throw new InvalidArgumentException('Kỳ không hợp lệ. Dùng dạng 2026-Q1, 2026-03 hoặc 2026.');
    }

    /**
     * @return array{
     *   period: array,
     *   profile: TaxProfile,
     *   revenue_gross: float,
     *   revenue_positive: float,
     *   adjustment_total: float,
     *   taxable_revenue: float,
     *   estimated_vat: float,
     *   estimated_pit: float,
     *   estimated_total: float,
     *   by_group: array<string, float>,
     *   by_day: Collection,
     *   ytd_revenue: float,
     *   threshold: ?float,
     *   threshold_ratio: ?float,
     *   threshold_warning: bool,
     *   entry_count: int,
     *   excluded_count: int,
     *   locked: bool,
     *   tax_period: ?TaxPeriod,
     *   days_to_due: ?int,
     * }
     */
    public function summarize(?string $periodKey = null): array
    {
        $profile = $this->profile();
        $period = $this->resolvePeriodRange($periodKey, $profile);
        $starts = $period['starts'];
        $ends = $period['ends'];

        $base = TaxLedgerEntry::query()
            ->whereDate('entry_date', '>=', $starts->toDateString())
            ->whereDate('entry_date', '<=', $ends->toDateString());

        $excludedCount = (clone $base)->where('is_excluded', true)->count();
        $taxable = (clone $base)->taxable();

        $revenuePositive = (float) (clone $taxable)->where('amount', '>', 0)->sum('amount');
        $adjustmentTotal = (float) (clone $taxable)->where('amount', '<', 0)->sum('amount');
        $taxableRevenue = (float) (clone $taxable)->sum('amount');
        $entryCount = (clone $taxable)->count();

        $byGroup = (clone $taxable)
            ->select('tax_group', DB::raw('SUM(amount) as total'))
            ->groupBy('tax_group')
            ->pluck('total', 'tax_group')
            ->map(fn ($v) => (float) $v)
            ->all();

        $byDay = (clone $taxable)
            ->selectRaw('entry_date as day')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as entries')
            ->groupBy('entry_date')
            ->orderBy('day')
            ->get();

        $vat = round($taxableRevenue * ((float) $profile->vat_rate) / 100, 0);
        $pit = round($taxableRevenue * ((float) $profile->pit_rate) / 100, 0);

        $ytdStart = Carbon::create($period['year'], 1, 1)->startOfDay();
        $ytdEnd = $ends->copy();
        $ytdRevenue = (float) TaxLedgerEntry::query()
            ->taxable()
            ->whereDate('entry_date', '>=', $ytdStart->toDateString())
            ->whereDate('entry_date', '<=', $ytdEnd->toDateString())
            ->sum('amount');

        $threshold = $profile->revenue_threshold !== null ? (float) $profile->revenue_threshold : null;
        $ratio = ($threshold && $threshold > 0) ? ($ytdRevenue / $threshold) : null;
        $warning = $ratio !== null && $ratio >= 0.8;

        $taxPeriod = TaxPeriod::where('period_key', $period['key'])->first();
        $locked = $taxPeriod?->isClosed() ?? false;

        $daysToDue = $period['due_on']->isPast()
            ? (int) $period['due_on']->diffInDays(now(), false) * -1
            : (int) now()->startOfDay()->diffInDays($period['due_on'], false);

        return [
            'period' => [
                'key' => $period['key'],
                'type' => $period['type'],
                'year' => $period['year'],
                'month' => $period['month'],
                'quarter' => $period['quarter'],
                'starts_on' => $starts->toDateString(),
                'ends_on' => $ends->toDateString(),
                'due_on' => $period['due_on']->toDateString(),
                'label' => $this->periodLabel($period),
            ],
            'profile' => $profile,
            'revenue_gross' => $revenuePositive + abs($adjustmentTotal),
            'revenue_positive' => $revenuePositive,
            'adjustment_total' => $adjustmentTotal,
            'taxable_revenue' => $taxableRevenue,
            'estimated_vat' => $vat,
            'estimated_pit' => $pit,
            'estimated_total' => $vat + $pit,
            'by_group' => $byGroup,
            'by_day' => $byDay,
            'ytd_revenue' => $ytdRevenue,
            'threshold' => $threshold,
            'threshold_ratio' => $ratio,
            'threshold_warning' => $warning,
            'entry_count' => $entryCount,
            'excluded_count' => $excludedCount,
            'locked' => $locked,
            'tax_period' => $taxPeriod,
            'days_to_due' => $daysToDue,
        ];
    }

    /**
     * Danh sách kỳ gần đây để chọn trên UI.
     *
     * @return list<array{key: string, label: string}>
     */
    public function recentPeriodOptions(int $count = 8): array
    {
        $profile = $this->profile();
        $cycle = $profile->filing_cycle ?: TaxProfile::CYCLE_QUARTER;
        $options = [];
        $cursor = now();

        for ($i = 0; $i < $count; $i++) {
            if ($cycle === TaxProfile::CYCLE_MONTH) {
                $d = $cursor->copy()->subMonths($i);
                $key = $d->format('Y-m');
            } elseif ($cycle === TaxProfile::CYCLE_YEAR) {
                $d = $cursor->copy()->subYears($i);
                $key = (string) $d->year;
            } else {
                $d = $cursor->copy()->subMonths($i * 3);
                $q = (int) ceil($d->month / 3);
                $key = $d->year.'-Q'.$q;
            }
            try {
                $range = $this->parsePeriodKey($key, $profile);
                $options[] = [
                    'key' => $range['key'],
                    'label' => $this->periodLabel($range),
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        // unique by key
        $seen = [];
        $unique = [];
        foreach ($options as $opt) {
            if (isset($seen[$opt['key']])) {
                continue;
            }
            $seen[$opt['key']] = true;
            $unique[] = $opt;
        }

        return $unique;
    }

    public function closePeriod(string $periodKey, ?User $user = null, ?string $note = null): TaxPeriod
    {
        $summary = $this->summarize($periodKey);
        if ($summary['locked']) {
            throw new InvalidArgumentException('Kỳ này đã khóa sổ.');
        }

        $period = $summary['period'];

        return TaxPeriod::updateOrCreate(
            ['period_key' => $period['key']],
            [
                'period_type' => $period['type'],
                'year' => $period['year'],
                'month' => $period['month'],
                'quarter' => $period['quarter'],
                'starts_on' => $period['starts_on'],
                'ends_on' => $period['ends_on'],
                'due_on' => $period['due_on'],
                'status' => TaxPeriod::STATUS_CLOSED,
                'revenue_total' => $summary['revenue_positive'],
                'adjustment_total' => $summary['adjustment_total'],
                'taxable_revenue' => $summary['taxable_revenue'],
                'estimated_vat' => $summary['estimated_vat'],
                'estimated_pit' => $summary['estimated_pit'],
                'estimated_total' => $summary['estimated_total'],
                'snapshot_json' => json_encode([
                    'by_group' => $summary['by_group'],
                    'entry_count' => $summary['entry_count'],
                    'ytd_revenue' => $summary['ytd_revenue'],
                    'vat_rate' => (float) $summary['profile']->vat_rate,
                    'pit_rate' => (float) $summary['profile']->pit_rate,
                    'closed_at_display' => now()->toDateTimeString(),
                ], JSON_UNESCAPED_UNICODE),
                'admin_note' => $note,
                'closed_by' => $user?->id,
                'closed_at' => now(),
            ]
        );
    }

    public function reopenPeriod(string $periodKey): TaxPeriod
    {
        $row = TaxPeriod::where('period_key', $periodKey)->first();
        if (! $row) {
            throw new InvalidArgumentException('Không tìm thấy kỳ đã khóa.');
        }
        $row->update([
            'status' => TaxPeriod::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $row->fresh();
    }

    public function markPaid(string $periodKey, float $amount, ?string $paidOn = null, ?string $ref = null): TaxPeriod
    {
        $row = TaxPeriod::where('period_key', $periodKey)->first();
        if (! $row) {
            // tạo period mở với số liệu hiện tại rồi ghi paid
            $summary = $this->summarize($periodKey);
            $p = $summary['period'];
            $row = TaxPeriod::create([
                'period_key' => $p['key'],
                'period_type' => $p['type'],
                'year' => $p['year'],
                'month' => $p['month'],
                'quarter' => $p['quarter'],
                'starts_on' => $p['starts_on'],
                'ends_on' => $p['ends_on'],
                'due_on' => $p['due_on'],
                'status' => TaxPeriod::STATUS_OPEN,
                'revenue_total' => $summary['revenue_positive'],
                'adjustment_total' => $summary['adjustment_total'],
                'taxable_revenue' => $summary['taxable_revenue'],
                'estimated_vat' => $summary['estimated_vat'],
                'estimated_pit' => $summary['estimated_pit'],
                'estimated_total' => $summary['estimated_total'],
            ]);
        }

        $row->update([
            'paid_amount' => $amount,
            'paid_on' => $paidOn ?: now()->toDateString(),
            'payment_ref' => $ref,
        ]);

        return $row->fresh();
    }

    public function addManualEntry(array $data, ?User $user = null): TaxLedgerEntry
    {
        $date = $data['entry_date'] ?? now()->toDateString();
        if ($this->isDateLocked($date)) {
            throw new InvalidArgumentException('Không thể ghi sổ: ngày thuộc kỳ đã khóa.');
        }

        $amount = (float) $data['amount'];
        $source = $data['source_type'] ?? TaxLedgerEntry::SOURCE_MANUAL;
        if ($source === TaxLedgerEntry::SOURCE_ADJUSTMENT && $amount > 0) {
            $amount = -abs($amount);
        }

        return TaxLedgerEntry::create([
            'entry_date' => $date,
            'source_type' => $source,
            'source_id' => null,
            'description' => $data['description'],
            'amount' => $amount,
            'tax_group' => $data['tax_group'] ?? TaxLedgerEntry::GROUP_COMMERCE,
            'payment_method' => $data['payment_method'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'invoice_status' => $data['invoice_status'] ?? TaxLedgerEntry::INVOICE_NONE,
            'invoice_number' => $data['invoice_number'] ?? null,
            'is_excluded' => ! empty($data['is_excluded']),
            'note' => $data['note'] ?? null,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * @return array{starts: Carbon, ends: Carbon, key: string, type: string, year: int, month: ?int, quarter: ?int, due_on: Carbon}
     */
    private function buildRange(
        string $key,
        string $type,
        Carbon $starts,
        Carbon $ends,
        TaxProfile $profile,
        int $year,
        ?int $month,
        ?int $quarter
    ): array {
        $offset = max(0, (int) $profile->filing_month_offset);
        $day = min(28, max(1, (int) $profile->filing_day));
        $due = $ends->copy()->startOfMonth()->addMonths($offset)->day($day);

        return [
            'key' => $key,
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            'starts' => $starts,
            'ends' => $ends,
            'due_on' => $due,
        ];
    }

    /**
     * @param  array{key: string, type: string, year: int, month: ?int, quarter: ?int, starts?: Carbon, ends?: Carbon, starts_on?: string, ends_on?: string}  $period
     */
    private function periodLabel(array $period): string
    {
        if ($period['type'] === TaxProfile::CYCLE_QUARTER) {
            return 'Quý '.$period['quarter'].'/'.$period['year'];
        }
        if ($period['type'] === TaxProfile::CYCLE_MONTH) {
            return 'Tháng '.sprintf('%02d', $period['month']).'/'.$period['year'];
        }

        return 'Năm '.$period['year'];
    }
}
