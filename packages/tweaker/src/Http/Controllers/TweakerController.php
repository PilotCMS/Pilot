<?php

namespace Tweaker\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tweaker\Http\Requests\UpdateModelRequest;

class TweakerController extends Controller
{
    public function script(): Response
    {
        $this->abortIfDisabled();

        $path = __DIR__.'/../../../../resources/tweaker.js';

        if (! File::exists($path)) {
            abort(404, 'Tweaker script not found.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
        ]);
    }

    public function config(): JsonResponse
    {
        $this->abortIfDisabled();

        return response()->json([
            'enabled' => true,
            'defaultLessPath' => config('tweaker.default_less_path'),
            'defaultBladePath' => config('tweaker.default_blade_path'),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $this->abortIfDisabled();

        $data = $request->validate([
            'kind' => ['required', 'string', 'in:less,blade'],
            'path' => ['nullable', 'string'],
            'rules' => ['nullable', 'array'],
            'rules.*.selector' => ['required_with:rules', 'string'],
            'rules.*.declarations' => ['required_with:rules', 'array'],
            'textEdits' => ['nullable', 'array'],
            'textEdits.*.selector' => ['required_with:textEdits', 'string'],
            'textEdits.*.text' => ['required_with:textEdits', 'string'],
        ]);

        $path = $this->resolvePath(
            $data['path'] ?? null,
            $data['kind'] === 'less'
                ? config('tweaker.default_less_path')
                : config('tweaker.default_blade_path')
        );

        $content = $data['kind'] === 'less'
            ? $this->buildLessContent($data['rules'] ?? [])
            : $this->buildBladeContent($data['textEdits'] ?? []);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);

        $this->logToDatabase($data['kind'], $path, $content);

        return response()->json([
            'saved' => true,
            'path' => $path,
        ]);
    }

    public function updateModel(UpdateModelRequest $request): JsonResponse
    {
        $data = $request->validated();

        $modelClass = $data['model'];
        $field = $data['field'];

        if (! class_exists($modelClass)) {
            abort(422, 'Model not found.');
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            abort(422, 'Invalid model.');
        }

        $allowedModels = config('tweaker.allowed_models', ['*']);
        if (! in_array('*', $allowedModels, true) && ! in_array($modelClass, $allowedModels, true)) {
            abort(403, 'Model not allowed.');
        }

        $allowedFields = config('tweaker.allowed_fields', ['*']);
        if (! in_array('*', $allowedFields, true) && ! in_array($field, $allowedFields, true)) {
            abort(403, 'Field not allowed.');
        }

        $record = $modelClass::query()->findOrFail($data['id']);

        if (! $record->isFillable($field)) {
            abort(403, 'Field not fillable.');
        }

        $record->{$field} = $data['value'];
        $record->save();

        return response()->json([
            'updated' => true,
            'model' => $modelClass,
            'id' => $record->getKey(),
            'field' => $field,
            'value' => $record->{$field},
        ]);
    }

    protected function buildLessContent(array $rules): string
    {
        $timestamp = now()->toDateTimeString();
        $blocks = [];

        foreach ($rules as $rule) {
            $declarations = [];
            foreach ($rule['declarations'] as $property => $value) {
                $declarations[] = "  {$property}: {$value};";
            }
            if ($declarations === []) {
                continue;
            }
            $blocks[] = $rule['selector']." {\n".implode("\n", $declarations)."\n}";
        }

        return "// Tweaker export {$timestamp}\n".implode("\n\n", $blocks)."\n";
    }

    protected function buildBladeContent(array $edits): string
    {
        $payload = json_encode($edits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<BLADE
<!-- Tweaker text overrides -->
<script>
(() => {
  const edits = {$payload};
  edits.forEach((edit) => {
    const nodes = document.querySelectorAll(edit.selector);
    nodes.forEach((node) => {
      node.textContent = edit.text;
    });
  });
})();
</script>
BLADE;
    }

    protected function resolvePath(?string $requested, string $fallback): string
    {
        $path = $requested ?: $fallback;

        $path = Str::startsWith($path, '/') ? $path : base_path($path);
        $normalized = realpath($path) ?: $path;

        $allowed = collect(config('tweaker.allowed_paths'))
            ->map(fn ($allowedPath) => Str::startsWith($allowedPath, '/') ? $allowedPath : base_path($allowedPath))
            ->map(fn ($allowedPath) => realpath($allowedPath) ?: $allowedPath)
            ->all();

        $isAllowed = collect($allowed)->contains(function ($allowedPath) use ($normalized) {
            return Str::startsWith($normalized, $allowedPath);
        });

        if (! $isAllowed) {
            abort(403, 'Path not allowed.');
        }

        if (! Str::endsWith($normalized, ['.less', '.blade.php'])) {
            abort(422, 'Unsupported file type.');
        }

        return $normalized;
    }

    protected function abortIfDisabled(): void
    {
        if (! config('tweaker.enabled')) {
            abort(403, 'Tweaker is disabled.');
        }
    }

    protected function logToDatabase(string $kind, string $path, string $content): void
    {
        if (! config('tweaker.log_to_database')) {
            return;
        }

        try {
            if (! DB::getSchemaBuilder()->hasTable('tweaker_changes')) {
                return;
            }

            DB::table('tweaker_changes')->insert([
                'kind' => $kind,
                'path' => $path,
                'payload' => $content,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            return;
        }
    }
}
