<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\ContentResource;
use App\Models\CmsSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pilot\Laravel\Models\Content;
use Pilot\Laravel\Models\Space;
use Pilot\Laravel\Support\ContentRenderer;

class ContentController extends Controller
{
    public function index(Request $request, ContentRenderer $renderer, $spaceSlug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', CmsSetting::get('default_locale', 'en'));
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            if (! CmsSetting::get('draft_api_enabled', true)) {
                return response()->json(['error' => 'Draft API access is disabled'], 403);
            }

            // Require Sanctum token for draft access
            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $contents = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->get();

        return response()->json([
            'contents' => ContentResource::collection(
                $contents->map(fn (Content $content) => $renderer->fromModel($content, $locale))
            ),
        ]);
    }

    public function show(Request $request, ContentRenderer $renderer, $spaceSlug, $slug): JsonResponse
    {
        $space = Space::where('slug', $spaceSlug)->firstOrFail();
        $locale = $request->get('locale', CmsSetting::get('default_locale', 'en'));
        $version = $request->get('version', 'published');

        $query = Content::where('space_id', $space->id)
            ->where('slug', $slug)
            ->where('type', 'page');

        if ($version === 'published') {
            $query->where('status', 'published')
                ->whereNotNull('published_at');
        } elseif ($version === 'draft') {
            if (! CmsSetting::get('draft_api_enabled', true)) {
                return response()->json(['error' => 'Draft API access is disabled'], 403);
            }

            if (! $request->user()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $content = $query->with(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children'])->firstOrFail();

        return response()->json([
            'story' => new ContentResource($renderer->fromModel($content, $locale)),
            'content' => new ContentResource($renderer->fromModel($content, $locale)),
        ]);
    }
}
