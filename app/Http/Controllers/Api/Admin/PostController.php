<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $query = Post::latest();

        if ($search = $request->string('q')->toString()) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $this->ok(PostResource::collection($query->paginate($perPage)->withQueryString()));
    }

    public function show(Post $post): JsonResponse
    {
        return $this->ok(new PostResource($post));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('posts/og', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Post::uniqueSlug($data['title']);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $post = Post::create($data);

        return $this->created(new PostResource($post), 'Đã đăng bài viết.');
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $this->validated($request, $post);

        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $data['image'] = $request->file('image')->store('posts', 'public');
        }
        if ($request->hasFile('og_image')) {
            if ($post->og_image) {
                Storage::disk('public')->delete($post->og_image);
            }
            $data['og_image'] = $request->file('og_image')->store('posts/og', 'public');
        }
        if (empty($data['slug'])) {
            $data['slug'] = Post::uniqueSlug($data['title'], $post->id);
        } else {
            $data['slug'] = Str::slug($data['slug']);
        }

        $post->update($data);

        return $this->ok(new PostResource($post->fresh()), 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return $this->ok(null, 'Đã chuyển bài viết vào thùng rác.');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug,'.($post?->id ?? 'NULL')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_image' => ['nullable', 'image', 'max:4096'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['is_published'] = $request->boolean('is_published', $post?->is_published ?? false);
        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = $post?->published_at ?? now();
        }

        return $data;
    }
}
