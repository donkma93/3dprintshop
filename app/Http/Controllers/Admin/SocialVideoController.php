<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialVideo;
use App\Services\SocialVideoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialVideoController extends Controller
{
    public function __construct(
        private readonly SocialVideoMetadataService $metadata
    ) {}

    public function index()
    {
        $videos = SocialVideo::query()
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.videos.index', compact('videos'));
    }

    public function create()
    {
        return view('admin.videos.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        SocialVideo::create($data);

        return redirect()
            ->route('admin.videos.index')
            ->with('success', 'Đã thêm video/live. Thumbnail đã lấy tự động từ mạng xã hội.');
    }

    public function edit(SocialVideo $video)
    {
        return view('admin.videos.edit', compact('video'));
    }

    public function update(Request $request, SocialVideo $video)
    {
        $data = $this->validated($request, $video);
        $video->update($data);

        return redirect()
            ->route('admin.videos.index')
            ->with('success', 'Đã cập nhật video/live.');
    }

    public function destroy(SocialVideo $video)
    {
        $video->delete();

        return redirect()
            ->route('admin.videos.index')
            ->with('success', 'Đã chuyển video vào thùng rác.');
    }

    /**
     * AJAX: preview metadata for a pasted URL (admin form).
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:1000'],
        ]);

        $meta = $this->metadata->resolve($data['url']);

        return response()->json([
            'ok' => true,
            'platform' => $meta['platform'],
            'platform_label' => SocialVideo::platformOptions()[$meta['platform']] ?? $meta['platform'],
            'external_id' => $meta['external_id'],
            'thumbnail' => $meta['thumbnail'],
            'title' => $meta['title'],
            'channel_name' => $meta['channel_name'],
            'url' => $meta['url'],
        ]);
    }

    private function validated(Request $request, ?SocialVideo $video = null): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:1000'],
            'platform' => ['nullable', Rule::in(array_keys(SocialVideo::platformOptions()))],
            'channel_name' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $meta = $this->metadata->resolve($data['url']);

        $platform = $data['platform'] ?: $meta['platform'];
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = $meta['title'] ?: 'Video '.($meta['platform'] ?? 'online');
        }

        $channel = trim((string) ($data['channel_name'] ?? ''));
        if ($channel === '' && ! empty($meta['channel_name'])) {
            $channel = $meta['channel_name'];
        }

        return [
            'title' => $title,
            'url' => $meta['url'] ?: $data['url'],
            'platform' => $platform,
            'external_id' => $meta['external_id'],
            'thumbnail' => $meta['thumbnail'],
            'preview_url' => null,
            'channel_name' => $channel !== '' ? $channel : null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'published_at' => $data['published_at'] ?? now(),
        ];
    }
}
