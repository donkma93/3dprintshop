@extends('layouts.admin')

@section('title', 'Sổ doanh thu thuế')
@section('subtitle', 'Chi tiết dòng sổ kỳ '.$summary['period']['label'])

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.tax.index', ['period' => $filters['period']]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Tổng quan</a>
    <a href="{{ route('admin.tax.report', ['period' => $filters['period']]) }}" class="btn btn-outline-dark btn-sm">Báo cáo kỳ</a>
    <a href="{{ route('admin.tax.export', ['period' => $filters['period']]) }}" class="btn btn-outline-success btn-sm">Xuất CSV</a>
    <form method="POST" action="{{ route('admin.tax.sync') }}" class="d-inline">
        @csrf
        <input type="hidden" name="from" value="{{ $period['starts']->toDateString() }}">
        <input type="hidden" name="to" value="{{ $period['ends']->toDateString() }}">
        <button class="btn btn-dark btn-sm" type="submit" @disabled($summary['locked'])>Đồng bộ bán hàng (kỳ này)</button>
    </form>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Kỳ</label>
            <select name="period" class="form-select">
                @foreach($periodOptions as $opt)
                    <option value="{{ $opt['key'] }}" @selected($opt['key'] === $filters['period'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tìm</label>
            <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}" placeholder="Mã, mô tả, KH...">
        </div>
        <div class="col-md-2">
            <label class="form-label">Nguồn</label>
            <select name="source" class="form-select">
                <option value="">Tất cả</option>
                @foreach($sourceOptions as $k => $label)
                    <option value="{{ $k }}" @selected($filters['source'] === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Nhóm</label>
            <select name="group" class="form-select">
                <option value="">Tất cả</option>
                @foreach($groupOptions as $k => $label)
                    <option value="{{ $k }}" @selected($filters['group'] === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Loại trừ</label>
            <select name="excluded" class="form-select">
                <option value="" @selected($filters['excluded'] === '')>Tất cả</option>
                <option value="0" @selected($filters['excluded'] === '0')>Tính thuế</option>
                <option value="1" @selected($filters['excluded'] === '1')>Đã loại trừ</option>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-dark btn-sm">Lọc</button>
            <span class="ms-2 small text-secondary">
                TT: <strong>{{ $fmt($summary['taxable_revenue']) }}</strong>
                · Ước thuế: <strong>{{ $fmt($summary['estimated_total']) }}</strong>
                @if($summary['locked']) · <span class="badge text-bg-dark">Khóa sổ</span> @endif
            </span>
        </div>
    </div>
</form>

@unless($summary['locked'])
<div class="card p-3 mb-3">
    <h3 class="h6 fw-bold">Ghi thủ công / điều chỉnh</h3>
    <form method="POST" action="{{ route('admin.tax.entries.store') }}" class="row g-2">
        @csrf
        <input type="hidden" name="period" value="{{ $filters['period'] }}">
        <div class="col-md-2">
            <label class="form-label">Ngày</label>
            <input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', now()->toDateString()) }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Loại</label>
            <select name="source_type" class="form-select" required>
                <option value="manual">Doanh thu thủ công</option>
                <option value="adjustment">Điều chỉnh / trả hàng</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Mô tả</label>
            <input type="text" name="description" class="form-control" value="{{ old('description') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Số tiền (đ)</label>
            <input type="number" step="1" name="amount" class="form-control" value="{{ old('amount') }}" required>
            <div class="form-text">Điều chỉnh: nhập dương → hệ thống ghi âm</div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Nhóm</label>
            <select name="tax_group" class="form-select">
                @foreach($groupOptions as $k => $label)
                    <option value="{{ $k }}" @selected(old('tax_group', 'commerce') === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-dark w-100" type="submit">Ghi</button>
        </div>
        <div class="col-md-2">
            <label class="form-label">KH</label>
            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">SĐT</label>
            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">HĐ</label>
            <select name="invoice_status" class="form-select">
                @foreach($invoiceOptions as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Số HĐ</label>
            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Ghi chú</label>
            <input type="text" name="note" class="form-control" value="{{ old('note') }}">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_excluded" value="1" id="exNew">
                <label class="form-check-label" for="exNew">Loại trừ khỏi thuế</label>
            </div>
        </div>
    </form>
</div>
@else
<div class="alert alert-secondary">Kỳ đã khóa sổ — không ghi thêm / sửa dòng (trừ khi mở lại kỳ).</div>
@endunless

<div class="card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
            <tr>
                <th>Mã</th>
                <th>Ngày</th>
                <th>Nguồn</th>
                <th>Mô tả</th>
                <th class="text-end">Số tiền</th>
                <th>Nhóm</th>
                <th>HĐ</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr class="{{ $entry->is_excluded ? 'table-warning' : '' }}">
                    <td class="small font-monospace">{{ $entry->entry_code }}</td>
                    <td class="small">{{ $entry->entry_date->format('d/m/Y') }}</td>
                    <td class="small">{{ $entry->source_label }}</td>
                    <td class="small">
                        {{ $entry->description }}
                        @if($entry->customer_name)
                            <div class="text-secondary">{{ $entry->customer_name }} {{ $entry->customer_phone }}</div>
                        @endif
                        @if($entry->is_excluded)
                            <span class="badge text-bg-warning">Loại trừ</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold {{ $entry->amount < 0 ? 'text-danger' : '' }}">{{ $fmt($entry->amount) }}</td>
                    <td class="small">{{ $entry->group_label }}</td>
                    <td class="small">{{ $entry->invoice_label }}@if($entry->invoice_number)<br><span class="text-secondary">{{ $entry->invoice_number }}</span>@endif</td>
                    <td class="text-nowrap">
                        @unless($summary['locked'])
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit{{ $entry->id }}">Sửa</button>
                        @if($entry->source_type !== 'product_sale')
                        <form method="POST" action="{{ route('admin.tax.entries.destroy', $entry) }}" class="d-inline" onsubmit="return confirm('Xóa dòng này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                        @endif
                        @endunless
                    </td>
                </tr>
                @unless($summary['locked'])
                <tr class="collapse" id="edit{{ $entry->id }}">
                    <td colspan="8" class="bg-light">
                        <form method="POST" action="{{ route('admin.tax.entries.update', $entry) }}" class="row g-2 p-2">
                            @csrf @method('PUT')
                            <input type="hidden" name="period" value="{{ $filters['period'] }}">
                            @if($entry->source_type === 'product_sale')
                                <div class="col-md-3">
                                    <label class="form-label">Trạng thái HĐ</label>
                                    <select name="invoice_status" class="form-select form-select-sm">
                                        @foreach($invoiceOptions as $k => $label)
                                            <option value="{{ $k }}" @selected($entry->invoice_status === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Số HĐ</label>
                                    <input type="text" name="invoice_number" class="form-control form-control-sm" value="{{ $entry->invoice_number }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ghi chú</label>
                                    <input type="text" name="note" class="form-control form-control-sm" value="{{ $entry->note }}">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_excluded" value="1" id="ex{{ $entry->id }}" @checked($entry->is_excluded)>
                                        <label class="form-check-label" for="ex{{ $entry->id }}">Loại trừ</label>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-2">
                                    <label class="form-label">Ngày</label>
                                    <input type="date" name="entry_date" class="form-control form-control-sm" value="{{ $entry->entry_date->toDateString() }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Mô tả</label>
                                    <input type="text" name="description" class="form-control form-control-sm" value="{{ $entry->description }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Số tiền</label>
                                    <input type="number" step="1" name="amount" class="form-control form-control-sm" value="{{ (float) $entry->amount }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Nhóm</label>
                                    <select name="tax_group" class="form-select form-select-sm">
                                        @foreach($groupOptions as $k => $label)
                                            <option value="{{ $k }}" @selected($entry->tax_group === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">HĐ</label>
                                    <select name="invoice_status" class="form-select form-select-sm">
                                        @foreach($invoiceOptions as $k => $label)
                                            <option value="{{ $k }}" @selected($entry->invoice_status === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Số HĐ</label>
                                    <input type="text" name="invoice_number" class="form-control form-control-sm" value="{{ $entry->invoice_number }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">KH</label>
                                    <input type="text" name="customer_name" class="form-control form-control-sm" value="{{ $entry->customer_name }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">SĐT</label>
                                    <input type="text" name="customer_phone" class="form-control form-control-sm" value="{{ $entry->customer_phone }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ghi chú</label>
                                    <input type="text" name="note" class="form-control form-control-sm" value="{{ $entry->note }}">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_excluded" value="1" id="ex{{ $entry->id }}" @checked($entry->is_excluded)>
                                        <label class="form-check-label" for="ex{{ $entry->id }}">Loại trừ</label>
                                    </div>
                                </div>
                            @endif
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-dark" type="submit">Lưu</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endunless
            @empty
                <tr><td colspan="8" class="text-center text-secondary py-4">Chưa có dòng sổ trong kỳ.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="p-3">{{ $entries->links() }}</div>
    @endif
</div>
@endsection
