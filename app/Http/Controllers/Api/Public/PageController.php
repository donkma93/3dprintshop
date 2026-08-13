<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends ApiController
{
    public function index(): JsonResponse
    {
        $pages = Page::published()
            ->where('show_in_menu', true)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'slug', 'sort_order', 'show_in_menu']);

        return $this->ok(PageResource::collection($pages));
    }

    public function show(string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return $this->ok(new PageResource($page));
    }
}
