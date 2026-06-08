<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\FrontendBlockUpdateRequest;
use App\Models\Block;
use App\Models\CmsSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class FrontendEditorController extends Controller
{
    public function script(): Response
    {
        abort_unless(config('cms.frontend_editor.enabled', true), 403);

        $script = View::make('cms.frontend-editor-script', [
            'blocksBaseUrl' => url('/_cms_editor/blocks'),
            'csrfToken' => csrf_token(),
            'locale' => CmsSetting::get('default_locale', app()->getLocale()),
        ])->render();

        return response($script, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ]);
    }

    public function show(Request $request, Block $block): \Illuminate\Http\JsonResponse
    {
        abort_unless(config('cms.frontend_editor.enabled', true), 403);

        $block->loadMissing(['blockType', 'content']);
        $locale = $request->string('locale', CmsSetting::get('default_locale', app()->getLocale()))->toString();

        return response()->json([
            'block' => [
                'id' => $block->id,
                'type' => $block->type,
                'name' => $block->blockType?->name ?? $block->type,
                'data' => $this->localizedData($block->data ?? [], $locale),
                'rawData' => $block->data ?? [],
                'schema' => $block->blockType?->schema ?? ['fields' => []],
                'content' => [
                    'id' => $block->content?->id,
                    'name' => $block->content?->name,
                    'slug' => $block->content?->slug,
                    'editUrl' => $block->content ? route('admin.content.edit', $block->content) : null,
                ],
            ],
        ]);
    }

    public function update(FrontendBlockUpdateRequest $request, Block $block): \Illuminate\Http\JsonResponse
    {
        $block->loadMissing(['blockType', 'content']);

        $locale = $request->string('locale', CmsSetting::get('default_locale', app()->getLocale()))->toString();
        $schemaFields = collect($block->blockType?->schema['fields'] ?? [])->keyBy('key');
        $data = $block->data ?? [];

        foreach ($request->fields() as $key => $value) {
            if (! $schemaFields->has($key)) {
                continue;
            }

            $field = $schemaFields->get($key);

            if (($field['translatable'] ?? false) === true) {
                $localizedValue = Arr::get($data, $key, []);
                $localizedValue = is_array($localizedValue) ? $localizedValue : [$locale => $localizedValue];
                $localizedValue[$locale] = $value;
                $data[$key] = $localizedValue;

                continue;
            }

            $data[$key] = $this->castFieldValue($field, $value);
        }

        $block->update(['data' => $data]);

        if ($block->content) {
            $block->content->update([
                'updated_by' => $request->user()?->id,
            ]);
        }

        return response()->json([
            'updated' => true,
            'block' => [
                'id' => $block->id,
                'type' => $block->type,
                'data' => $this->localizedData($block->fresh()->data ?? [], $locale),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function localizedData(array $data, string $locale): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists($locale, $value)) {
                $data[$key] = $value[$locale];
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function castFieldValue(array $field, mixed $value): mixed
    {
        return match ($field['type'] ?? 'text') {
            'number' => is_numeric($value) ? $value + 0 : null,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }
}
