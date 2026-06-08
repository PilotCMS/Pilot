<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\Space;
use App\Support\Cms\ContentRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageController extends Controller
{
    public function __construct(
        protected ContentRenderer $renderer,
    ) {}

    public function home(): View
    {
        return $this->renderPage(CmsSetting::get('home_slug', config('cms.home_slug', 'home')));
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

        $payload = $this->renderer->fromModel($content, CmsSetting::get('default_locale', app()->getLocale()));

        $theme = CmsSetting::get('theme', config('cms.theme', 'default'));

        return view("themes.{$theme}.page", [
            'content' => $payload,
            'space' => $space,
            'blocks' => collect($payload->toArray()['body']),
            'theme' => $theme,
        ]);
    }

    protected function resolveSpace(): ?Space
    {
        $spaceSlug = CmsSetting::get('default_space', config('cms.default_space'));

        if ($spaceSlug) {
            return Space::query()->where('slug', $spaceSlug)->first();
        }

        return Space::query()->orderBy('id')->first();
    }
}
