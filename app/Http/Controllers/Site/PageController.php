<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Space;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageController extends Controller
{
    public function home(): View
    {
        return $this->renderPage(config('cms.home_slug', 'home'));
    }

    public function show(string $slug): View
    {
        return $this->renderPage($slug);
    }

    protected function renderPage(string $slug): View
    {
        $space = $this->resolveSpace();

        if (! $space) {
            throw new ModelNotFoundException('No space configured for public site rendering.');
        }

        $content = Content::query()
            ->where('space_id', $space->id)
            ->where('type', 'page')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->with([
                'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
                'blocks.children',
            ])
            ->firstOrFail();

        $blocks = $content->blocks
            ->map(fn ($block) => $this->transformBlock($block, app()->getLocale()))
            ->values();

        $theme = config('cms.theme', 'default');

        return view("themes.{$theme}.page", [
            'content' => $content,
            'space' => $space,
            'blocks' => $blocks,
            'theme' => $theme,
        ]);
    }

    protected function resolveSpace(): ?Space
    {
        $spaceSlug = config('cms.default_space');

        if ($spaceSlug) {
            return Space::query()->where('slug', $spaceSlug)->first();
        }

        return Space::query()->orderBy('id')->first();
    }

    protected function transformBlock($block, string $locale): array
    {
        $data = $block->data ?? [];

        foreach ($data as $key => $value) {
            if (is_array($value) && array_key_exists($locale, $value)) {
                $data[$key] = $value[$locale];
            }
        }

        return [
            'id' => $block->id,
            'component' => $block->type,
            'data' => $data,
            'children' => $block->children
                ->sortBy('position')
                ->map(fn ($child) => $this->transformBlock($child, $locale))
                ->values()
                ->all(),
        ];
    }
}
