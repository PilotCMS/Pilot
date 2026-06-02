<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function index(Request $request, $spaceSlug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', 'en');
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            // Require Sanctum token for draft access
            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $contents = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->get();

        return response()->json([
            'contents' => $contents->map(function ($content) use ($locale) {
                return $this->formatContent($content, $locale);
            }),
        ]);
    }

    public function show(Request $request, $spaceSlug, $slug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', 'en');
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('slug', $slug)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $content = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->firstOrFail();

        return response()->json([
            'content' => $this->formatContent($content, $locale),
        ]);
    }

    protected function formatContent(Content $content, string $locale): array
    {
        return [
            'id' => $content->id,
            'slug' => $content->slug,
            'name' => $content->name,
            'status' => $content->status,
            'published_at' => $content->published_at?->toIso8601String(),
            'body' => $content->blocks->map(function ($block) use ($locale) {
                return $this->formatBlock($block, $locale);
            })->values()->toArray(),
        ];
    }

    protected function formatBlock($block, string $locale): array
    {
        $data = $block->data;

        // Handle translatable fields
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[$locale])) {
                $data[$key] = $value[$locale];
            }
        }

        return [
            '_uid' => $block->id,
            'component' => $block->type,
            'data' => $data,
            'children' => $block->children->map(function ($child) use ($locale) {
                return $this->formatBlock($child, $locale);
            })->values()->toArray(),
        ];
    }
}
