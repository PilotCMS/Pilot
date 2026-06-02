<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PreviewController extends Controller
{
    /**
     * Return draft content for preview. Requires signed URL.
     */
    public function show(Request $request, Content $content): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid or expired preview link'], 403);
        }

        $locale = $request->get('locale', 'en');

        $content->load(['blocks' => function ($q) {
            $q->whereNull('parent_block_id')->orderBy('position');
        }, 'blocks.children']);

        return response()->json([
            'content' => [
                'id' => $content->id,
                'slug' => $content->slug,
                'name' => $content->name,
                'status' => $content->status,
                'published_at' => $content->published_at?->toIso8601String(),
                'meta' => $content->meta,
                'body' => $content->blocks->map(function ($block) use ($locale) {
                    return $this->formatBlock($block, $locale);
                })->values()->toArray(),
            ],
        ]);
    }

    protected function formatBlock($block, string $locale): array
    {
        $data = $block->data ?? [];

        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[$locale])) {
                $data[$key] = $value[$locale];
            }
        }

        return [
            '_uid' => $block->id,
            'component' => $block->type,
            'data' => $data,
            'children' => $block->children->map(fn ($child) => $this->formatBlock($child, $locale))->values()->toArray(),
        ];
    }

    public static function signedUrl(Content $content, int $expiresMinutes = 60): string
    {
        return URL::temporarySignedRoute(
            'api.preview.show',
            now()->addMinutes($expiresMinutes),
            ['content' => $content->id]
        );
    }
}
