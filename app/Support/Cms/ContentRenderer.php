<?php

namespace App\Support\Cms;

use App\Models\Content;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ContentRenderer
{
    public function fromModel(Content $content, ?string $locale = null): ContentPayload
    {
        $content->loadMissing([
            'blocks' => fn ($query) => $query->whereNull('parent_block_id')->orderBy('position'),
            'blocks.children',
        ]);

        return ContentPayload::fromModel($content, $locale ?? app()->getLocale());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fromHeadless(array $payload, ?string $locale = null): ContentPayload
    {
        return ContentPayload::fromArray($payload, $locale ?? app()->getLocale());
    }

    public function pageView(ContentPayload $content, ?string $theme = null, mixed $space = null): View
    {
        $theme ??= config('cms.theme', 'default');

        return view("themes.{$theme}.page", [
            'content' => $content,
            'space' => $space,
            'blocks' => $this->blockArrays($content->blocks),
            'theme' => $theme,
        ]);
    }

    public function renderPage(ContentPayload $content, ?string $theme = null): HtmlString
    {
        return new HtmlString($this->pageView($content, $theme)->render());
    }

    public function renderBlocks(ContentPayload $content, ?string $theme = null): HtmlString
    {
        $theme ??= config('cms.theme', 'default');

        return new HtmlString(view("themes.{$theme}.partials.blocks", [
            'blocks' => $this->blockArrays($content->blocks),
            'theme' => $theme,
        ])->render());
    }

    /**
     * @param  Collection<int, BlockPayload>  $blocks
     * @return Collection<int, array<string, mixed>>
     */
    protected function blockArrays(Collection $blocks): Collection
    {
        return $blocks->map(fn (BlockPayload $block): array => $block->toArray())->values();
    }
}
