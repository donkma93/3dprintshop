<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(50, max(1, (int) $request->query('per_page', 9)));
        $posts = Post::published()->latest('published_at')->paginate($perPage);

        return $this->ok(PostResource::collection($posts));
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $related = Post::published()->where('id', '!=', $post->id)->latest('published_at')->take(4)->get();

        return $this->ok([
            'post' => new PostResource($post),
            'related' => PostResource::collection($related),
        ]);
    }
}
