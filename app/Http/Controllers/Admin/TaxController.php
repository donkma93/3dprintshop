<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaxLedgerEntry;
use App\Models\TaxPeriod;
use App\Models\TaxProfile;
use App\Services\TaxPreparationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxController extends Controller
{
    public function __construct(private TaxPreparationService $tax)
    {
    }

    /**
     * Dashboard chuẩn bị thuế: tổng hợp kỳ, cảnh báo ngưỡng / hạn.
     */
    public function index(Request $request)
    {
        $periodKey = $request->query('period');
        try {
            $summary = $this->tax->summarize($periodKey ?: null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.tax.index')->with('error', $e->getMessage());
        }

        $periodOptions = $this->tax->recentPeriodOptions(10);
        $closedPeriods = TaxPeriod::query()->where('status', TaxPeriod::STATUS_CLOSED)
            ->orderByDesc('ends_on')
            ->limit(6)
            ->get();

        return view('admin.tax.index', compact('summary', 'periodOptions', 'closedPeriods'));
    }

    public function profile()
    {
        $profile = $this->tax->profile();

        return view('admin.tax.profile', [
            'profile' => $profile,
            'methodOptions' => TaxProfile::methodOptions(),
            'cycleOptions' => TaxProfile::cycleOptions(),
        ]);
    }

    public function updateProfile(Request $request)
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

        return redirect()
            ->route('admin.tax.profile')
            ->with('success', 'Đã lưu hồ sơ thuế hộ kinh doanh.');
    }

    public function ledger(Request $request)
    {
        $periodKey = $request->query('period');
        try {
            $period = $this->tax->resolvePeriodRange($periodKey ?: null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.tax.ledger')->with('error', $e->getMessage());
        }

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
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('customer_phone', 'like', "%{$q}%")
                    ->orWhere('invoice_number', 'like', "%{$q}%");
            });
        }

        if ($source = $request->query('source')) {
            $query->where('source_type', $source);
        }

        if ($group = $request->query('group')) {
            $query->where('tax_group', $group);
        }

        if ($request->query('excluded') === '1') {
            $query->where('is_excluded', true);
        } elseif ($request->query('excluded') === '0') {
            $query->where('is_excluded', false);
        }

        $entries = $query->paginate(30)->withQueryString();
        $periodOptions = $this->tax->recentPeriodOptions(10);
        $summary = $this->tax->summarize($period['key']);

        return view('admin.tax.ledger', [
            'entries' => $entries,
            'period' => $period,
            'periodOptions' => $periodOptions,
            'summary' => $summary,
            'sourceOptions' => TaxLedgerEntry::sourceOptions(),
            'groupOptions' => TaxLedgerEntry::groupOptions(),
            'invoiceOptions' => TaxLedgerEntry::invoiceOptions(),
            'filters' => [
                'q' => $request->query('q', ''),
                'source' => $request->query('source', ''),
                'group' => $request->query('group', ''),
                'excluded' => $request->query('excluded', ''),
                'period' => $period['key'],
            ],
        ]);
    }

    public function storeEntry(Request $request)
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
            'period' => ['nullable', 'string', 'max:20'],
        ]);

        $data['is_excluded'] = $request->boolean('is_excluded');

        try {
            $this->tax->addManualEntry($data, $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tax.ledger', array_filter(['period' => $data['period'] ?? null]))
            ->with('success', 'Đã ghi sổ doanh thu / điều chỉnh.');
    }

    public function updateEntry(Request $request, TaxLedgerEntry $entry)
    {
        if ($entry->source_type === TaxLedgerEntry::SOURCE_PRODUCT_SALE) {
            // Chỉ cho phép loại trừ / ghi chú / HĐ cho dòng đồng bộ từ bán hàng
            $data = $request->validate([
                'invoice_status' => ['nullable', Rule::in(array_keys(TaxLedgerEntry::invoiceOptions()))],
                'invoice_number' => ['nullable', 'string', 'max:80'],
                'is_excluded' => ['nullable', 'boolean'],
                'note' => ['nullable', 'string', 'max:500'],
                'period' => ['nullable', 'string', 'max:20'],
            ]);

            if ($this->tax->isDateLocked($entry->entry_date->toDateString())) {
                return back()->with('error', 'Không thể sửa: ngày thuộc kỳ đã khóa.');
            }

            $entry->update([
                'invoice_status' => $data['invoice_status'] ?? $entry->invoice_status,
                'invoice_number' => $data['invoice_number'] ?? $entry->invoice_number,
                'is_excluded' => $request->boolean('is_excluded'),
                'note' => $data['note'] ?? $entry->note,
            ]);

            return back()->with('success', 'Đã cập nhật dòng sổ (bán hàng).');
        }

        if ($this->tax->isDateLocked($entry->entry_date->toDateString())) {
            return back()->with('error', 'Không thể sửa: ngày thuộc kỳ đã khóa.');
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
            'period' => ['nullable', 'string', 'max:20'],
        ]);

        if ($this->tax->isDateLocked($data['entry_date'])) {
            return back()->withInput()->with('error', 'Không thể chuyển sang ngày thuộc kỳ đã khóa.');
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

        return back()->with('success', 'Đã cập nhật dòng sổ.');
    }

    public function destroyEntry(TaxLedgerEntry $entry)
    {
        if ($entry->source_type === TaxLedgerEntry::SOURCE_PRODUCT_SALE) {
            return back()->with('error', 'Không xóa dòng đồng bộ từ bán hàng. Hãy đánh dấu loại trừ nếu cần.');
        }

        if ($this->tax->isDateLocked($entry->entry_date->toDateString())) {
            return back()->with('error', 'Không thể xóa: ngày thuộc kỳ đã khóa.');
        }

        $entry->delete();

        return back()->with('success', 'Đã xóa dòng sổ thủ công.');
    }

    public function sync(Request $request)
    {
        $from = $request->filled('from') ? \Carbon\Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->filled('to') ? \Carbon\Carbon::parse($request->input('to'))->endOfDay() : null;

        $count = $this->tax->syncSalesToLedger($from, $to);

        return back()->with('success', "Đã đồng bộ {$count} giao dịch bán vào sổ thuế.");
    }

    public function report(Request $request)
    {
        $periodKey = $request->query('period');
        try {
            $summary = $this->tax->summarize($periodKey ?: null);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('admin.tax.report')->with('error', $e->getMessage());
        }

        $periodOptions = $this->tax->recentPeriodOptions(12);
        $print = $request->boolean('print');

        return view($print ? 'admin.tax.report-print' : 'admin.tax.report', compact(
            'summary',
            'periodOptions',
            'print'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $periodKey = $request->query('period');
        try {
            $period = $this->tax->resolvePeriodRange($periodKey ?: null);
            $summary = $this->tax->summarize($period['key']);
        } catch (InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        $filename = 'so-thue-'.$period['key'].'.csv';

        return response()->streamDownload(function () use ($period, $summary) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['BÁO CÁO CHUẨN BỊ THUẾ HỘ KINH DOANH']);
            fputcsv($out, ['Kỳ', $summary['period']['label']]);
            fputcsv($out, ['Từ ngày', $summary['period']['starts_on']]);
            fputcsv($out, ['Đến ngày', $summary['period']['ends_on']]);
            fputcsv($out, ['Hạn tham chiếu', $summary['period']['due_on']]);
            fputcsv($out, ['Hộ KD', $summary['profile']->business_name]);
            fputcsv($out, ['MST', $summary['profile']->tax_code]);
            fputcsv($out, []);
            fputcsv($out, ['Doanh thu dương', $summary['revenue_positive']]);
            fputcsv($out, ['Điều chỉnh', $summary['adjustment_total']]);
            fputcsv($out, ['Doanh thu tính thuế', $summary['taxable_revenue']]);
            fputcsv($out, ['Ước GTGT (%)', (float) $summary['profile']->vat_rate]);
            fputcsv($out, ['Ước GTGT (đ)', $summary['estimated_vat']]);
            fputcsv($out, ['Ước TNCN (%)', (float) $summary['profile']->pit_rate]);
            fputcsv($out, ['Ước TNCN (đ)', $summary['estimated_pit']]);
            fputcsv($out, ['Tổng ước thuế', $summary['estimated_total']]);
            fputcsv($out, ['Doanh thu lũy kế năm', $summary['ytd_revenue']]);
            fputcsv($out, ['Ngưỡng cảnh báo', $summary['threshold']]);
            fputcsv($out, []);
            fputcsv($out, [
                'Mã', 'Ngày', 'Nguồn', 'Mô tả', 'Nhóm', 'Số tiền',
                'KH', 'SĐT', 'HĐ', 'Số HĐ', 'Loại trừ', 'Ghi chú',
            ]);

            TaxLedgerEntry::query()
                ->whereDate('entry_date', '>=', $period['starts']->toDateString())
                ->whereDate('entry_date', '<=', $period['ends']->toDateString())
                ->orderBy('entry_date')
                ->orderBy('id')
                ->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->entry_code,
                            $row->entry_date->format('Y-m-d'),
                            $row->source_label,
                            $row->description,
                            $row->group_label,
                            (float) $row->amount,
                            $row->customer_name,
                            $row->customer_phone,
                            $row->invoice_label,
                            $row->invoice_number,
                            $row->is_excluded ? 'yes' : 'no',
                            $row->note,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function closePeriod(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->tax->closePeriod($data['period'], $request->user(), $data['admin_note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tax.report', ['period' => $data['period']])
            ->with('success', 'Đã khóa sổ kỳ '.$data['period'].'.');
    }

    public function reopenPeriod(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
        ]);

        try {
            $this->tax->reopenPeriod($data['period']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.tax.report', ['period' => $data['period']])
            ->with('success', 'Đã mở lại kỳ '.$data['period'].'.');
    }

    public function markPaid(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', 'string', 'max:20'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'paid_on' => ['nullable', 'date'],
            'payment_ref' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $this->tax->markPaid(
                $data['period'],
                (float) $data['paid_amount'],
                $data['paid_on'] ?? null,
                $data['payment_ref'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận số tiền đã nộp thuế.');
    }
}
