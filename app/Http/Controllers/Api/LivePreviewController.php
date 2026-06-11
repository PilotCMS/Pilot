<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LivePreviewRenderRequest;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\Space;
use Illuminate\Http\JsonResponse;
use Pilot\Laravel\Support\ContentRenderer;

class LivePreviewController extends Controller
{
    public function __invoke(LivePreviewRenderRequest $request, ContentRenderer $renderer): JsonResponse
    {
        $locale = $request->string('locale', CmsSetting::get('default_locale', app()->getLocale()))->toString();

        $content = $request->source() === 'headless'
            ? $renderer->fromHeadless($this->headlessPayload($request->validated()), $locale)
            : $renderer->fromModel($this->resolveContent($request), $locale);

        return response()->json([
            'html' => $renderer->renderBlocks($content)->toHtml(),
            'content' => $content->toArray(),
            'source' => $content->source,
        ]);
    }

    protected function resolveContent(LivePreviewRenderRequest $request): Content
    {
        if ($request->filled('content_id')) {
            return $this->authorizePreviewContent($request, Content::query()->findOrFail($request->integer('content_id')));
        }

        $spaceSlug = $request->string('space', CmsSetting::get('default_space', config('pilot.default_space')))->toString();
        $slug = $request->string('slug', CmsSetting::get('home_slug', config('pilot.home_slug', 'home')))->toString();

        $space = $spaceSlug
            ? Space::query()->where('slug', $spaceSlug)->firstOrFail()
            : Space::query()->orderBy('id')->firstOrFail();

        return $this->authorizePreviewContent($request, Content::query()
            ->where('space_id', $space->id)
            ->where('type', 'page')
            ->where('slug', $slug)
            ->firstOrFail());
    }

    protected function authorizePreviewContent(LivePreviewRenderRequest $request, Content $content): Content
    {
        if ($content->isPublished() || $request->hasValidSignature() || $request->user()) {
            return $content;
        }

        abort(403, 'Draft preview content requires an authenticated or signed request.');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function headlessPayload(array $payload): array
    {
        if (isset($payload['content']) || isset($payload['story'])) {
            return $payload;
        }

        return [
            'content' => [
                'slug' => $payload['slug'] ?? 'preview',
                'name' => $payload['name'] ?? 'Preview',
                'body' => $payload['body'] ?? $payload['blocks'] ?? [],
            ],
        ];
    }
}
