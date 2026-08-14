@extends('layouts.admin')

@section('title', 'Video mạng xã hội')
@section('subtitle', 'YouTube / TikTok / Facebook (video & live) — dải nổi trang shop, tối đa 10 mới nhất')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="text-secondary">Tổng {{ $videos->total() }} · Chỉ link online · Thumbnail tự lấy</div>
    <a href="{{ route('admin.videos.create') }}" class="btn btn-dark btn-sm">+ Thêm link</a>
</div>

<div class="alert alert-light border small mb-3">
    <strong>Cách dùng:</strong> dán link YouTube (watch / Shorts / Live), TikTok (video / Live) hoặc Facebook.
    Hệ thống <strong>tự lấy thumbnail</strong> — không upload ảnh, không file offline.
    Live stream cũng hiển thị trên dải video shop như video thường.
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
            <tr>
                <th style="width:120px">Thumbnail</th>
                <th>Tiêu đề</th>
                <th>Nền tảng</th>
                <th>Ngày</th>
                <th>TT</th>
                <th class="text-end">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            @forelse($videos as $video)
                <tr>
                    <td>
                        <img src="{{ $video->thumbnail_url }}" width="112" height="64" class="rounded border" style="object-fit:cover;background:#e2e8f0" alt="">
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $video->title }}</div>
                        @if($video->channel_name)
                            <div class="small text-secondary">{{ $video->channel_name }}</div>
                        @endif
                        <a href="{{ $video->url }}" target="_blank" rel="noopener" class="small text-truncate d-inline-block" style="max-width:280px">{{ $video->url }}</a>
                    </td>
                    <td>
                        <span class="badge text-bg-{{ $video->platform === 'youtube' ? 'danger' : ($video->platform === 'tiktok' ? 'dark' : 'primary') }}">
                            {{ $video->platform_label }}
                        </span>
                        @if(is_string($video->external_id) && str_starts_with($video->external_id, 'live:'))
                            <span class="badge text-bg-danger">Live</span>
                        @endif
                    </td>
                    <td class="small">
                        {{ optional($video->published_at)->format('d/m/Y H:i') ?: '—' }}
                        <div class="text-secondary">#{{ $video->sort_order }}</div>
                    </td>
                    <td>
                        @if($video->is_active)
                            <span class="badge badge-soft">Hiện</span>
                        @else
                            <span class="badge text-bg-secondary">Ẩn</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.videos.edit', $video) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.videos.destroy', $video) }}" method="POST" class="d-inline"
                              data-confirm="Chuyển video vào thùng rác?" data-confirm-title="Xóa video">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-secondary">Chưa có link. Thêm YouTube/TikTok (video hoặc live) để hiện dải nổi trên shop.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $videos->links() }}</div>
</div>
@endsection
