<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\ContentResource;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Support\Cms\ContentRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PreviewController extends Controller
{
    /**
     * Return draft content for preview. Requires signed URL.
     */
    public function show(Request $request, Content $content, ContentRenderer $renderer): JsonResponse
    {
        if (! CmsSetting::get('preview_links_enabled', true)) {
            return response()->json(['error' => 'Preview links are disabled'], 403);
        }

        if (! $request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid or expired preview link'], 403);
        }

        $locale = $request->get('locale', CmsSetting::get('default_locale', 'en'));

        return response()->json([
            'story' => new ContentResource($renderer->fromModel($content, $locale)),
            'content' => new ContentResource($renderer->fromModel($content, $locale)),
        ]);
    }

    public static function signedUrl(Content $content, int $expiresMinutes = 60): string
    {
        $expiresMinutes = CmsSetting::get('preview_expiration_minutes', $expiresMinutes);

        return URL::temporarySignedRoute(
            'api.preview.show',
            now()->addMinutes($expiresMinutes),
            ['content' => $content->id]
        );
    }
}
