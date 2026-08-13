@extends('layouts.admin')

@section('title', 'Sản phẩm in 3D')
@section('subtitle', 'Đăng và quản lý sản phẩm hiển thị trên web')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex gap-2" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên / SKU">
        <select name="category_id" class="form-select form-select-sm" style="width:auto">
            <option value="">Tất cả danh mục</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-dark">Lọc</button>
    </form>
    <a href="{{ route('admin.products.create') }}" class="btn btn-dark btn-sm">+ Đăng sản phẩm</a>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá bán</th>
                @if(auth()->user()->canViewRevenue())
                <th>Giá thành</th>
                @endif
                <th>Kho</th>
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $product->image_url }}" alt="" width="48" height="48" class="rounded object-fit-cover" style="object-fit:cover">
                            <div>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <div class="small text-secondary">{{ $product->sku ?: $product->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td class="fw-semibold">
                        @if($product->is_on_sale)
                            <div class="small text-decoration-line-through text-secondary">{{ number_format($product->price, 0, ',', '.') }} đ</div>
                            <div class="text-danger">{{ number_format($product->final_price, 0, ',', '.') }} đ</div>
                            <span class="badge text-bg-danger">{{ $product->sale_badge }}</span>
                        @else
                            {{ number_format($product->price, 0, ',', '.') }} đ
                        @endif
                    </td>
                    @if(auth()->user()->canViewRevenue())
                    <td>{{ number_format($product->cost_price, 0, ',', '.') }} đ</td>
                    @endif
                    <td>{{ $product->stock }}</td>
                    <td>
                        @if($product->is_active)
                            <span class="badge badge-soft">Hiện</span>
                        @else
                            <span class="badge text-bg-secondary">Ẩn</span>
                        @endif
                        @if($product->is_featured)
                            <span class="badge text-bg-warning">Nổi bật</span>
                        @endif
                        @if($product->is_on_sale)
                            <span class="badge text-bg-danger">Sale</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.products.qr', $product) }}" class="btn btn-sm btn-outline-dark" title="Mã QR">QR</a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline" data-confirm="Chuyển sản phẩm này vào thùng rác? Có thể khôi phục trong 30 ngày." data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ auth()->user()->canViewRevenue() ? 7 : 6 }}" class="text-secondary">Chưa có sản phẩm.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $products->links() }}</div>
</div>
@endsection
