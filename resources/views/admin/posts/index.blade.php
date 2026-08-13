@extends('layouts.admin')
@section('title', 'Bài viết')
@section('subtitle', 'Viết bài tin tức / kiến thức SEO')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm bài viết...">
        <button class="btn btn-sm btn-outline-dark">Tìm</button>
    </form>
    <a href="{{ route('admin.posts.create') }}" class="btn btn-dark btn-sm">+ Viết bài</a>
</div>
<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
            <tr>
                <th>Bài viết</th>
                <th>SEO title</th>
                <th>Ngày</th>
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($posts as $post)
                <tr>
                    <td>
                        <div class="d-flex gap-2 align-items-center">
                            <img src="{{ $post->image_url }}" width="56" height="40" class="rounded" style="object-fit:cover" alt="">
                            <div>
                                <div class="fw-semibold">{{ $post->title }}</div>
                                <div class="small text-secondary">{{ $post->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ Str::limit($post->meta_title ?: $post->title, 40) }}</td>
                    <td>{{ optional($post->published_at)->format('d/m/Y') }}</td>
                    <td>
                        @if($post->is_published)
                            <span class="badge badge-soft">Xuất bản</span>
                        @else
                            <span class="badge text-bg-secondary">Nháp</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" class="d-inline"
                              data-confirm="Chuyển bài viết vào thùng rác?" data-confirm-title="Đưa vào thùng rác">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-secondary">Chưa có bài viết.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $posts->links() }}</div>
</div>
@endsection
