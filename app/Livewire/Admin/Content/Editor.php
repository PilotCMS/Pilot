<?php

namespace App\Livewire\Admin\Content;

use App\Http\Controllers\Api\PreviewController;
use App\Models\Activity;
use App\Models\Asset;
use App\Models\Block;
use App\Models\BlockType;
use App\Models\Content;
use App\Models\ContentRevision;
use App\Models\EditorPreference;
use Illuminate\Support\Str;
use Livewire\Component;

class Editor extends Component
{
    public Content $content;

    public $selectedBlockId = null;

    public $blocks = [];

    public $blockTypes = [];

    public $blockLibraryOpen = false;

    public $addBlockPosition = null; // 'above'|'below'|null, when set opens library to insert at position

    public $addBlockParentId = null;

    public $addBlockColumnIndex = null;

    public $drawerOpen = true;

    public $leftSidebarCollapsed = false;

    public $rightPanelTab = 'content';

    public $lastSavedAt = null;

    public $savedJustNow = false;

    public int $previewVersion = 1;

    protected $listeners = [
        'block-updated' => 'handleBlockUpdated',
        'asset-selected' => 'handleAssetSelected',
        'open-asset-picker' => 'handleOpenAssetPicker',
    ];

    public function mount(Content $content)
    {
        $this->content = $content;
        $this->loadBlocks();
        $this->blockTypes = BlockType::all()->keyBy('key');

        // Load editor preferences
        $prefs = EditorPreference::get(auth()->id(), 'editor', []);
        $this->leftSidebarCollapsed = $prefs['leftSidebarCollapsed'] ?? false;
        $this->drawerOpen = $prefs['drawerOpen'] ?? true;
    }

    public function loadBlocks()
    {
        $this->blocks = $this->content->blocks()
            ->with(['children' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    public function toggleLeftSidebar()
    {
        $this->leftSidebarCollapsed = ! $this->leftSidebarCollapsed;
        $this->saveEditorPreference('leftSidebarCollapsed', $this->leftSidebarCollapsed);
    }

    public function toggleDrawer()
    {
        $this->drawerOpen = ! $this->drawerOpen;
        $this->saveEditorPreference('drawerOpen', $this->drawerOpen);
    }

    protected function saveEditorPreference(string $key, mixed $value): void
    {
        $prefs = EditorPreference::get(auth()->id(), 'editor', []);
        $prefs[$key] = $value;
        EditorPreference::set(auth()->id(), 'editor', $prefs);
    }

    public function addBlock($blockTypeKey, $position = null)
    {
        $blockType = BlockType::where('key', $blockTypeKey)->firstOrFail();
        $parentId = $this->addBlockParentId ? (int) $this->addBlockParentId : null;

        $insertPosition = 0;
        if ($position !== null && is_numeric($position)) {
            $insertPosition = (int) $position;
        } elseif ($this->addBlockPosition === 'inside' && $parentId) {
            $insertPosition = Block::where('content_id', $this->content->id)
                ->where('parent_block_id', $parentId)
                ->count();
        } elseif ($this->addBlockPosition === 'above' && $this->selectedBlockId) {
            $selectedBlock = Block::where('content_id', $this->content->id)->findOrFail($this->selectedBlockId);
            $parentId = $selectedBlock->parent_block_id;
            $insertPosition = $selectedBlock->position;
        } elseif ($this->addBlockPosition === 'below' && $this->selectedBlockId) {
            $selectedBlock = Block::where('content_id', $this->content->id)->findOrFail($this->selectedBlockId);
            $parentId = $selectedBlock->parent_block_id;
            $insertPosition = $selectedBlock->position + 1;
        } else {
            $insertPosition = $this->blocks ? max(array_column($this->blocks, 'position')) + 1 : 0;
        }

        // Shift positions
        Block::where('content_id', $this->content->id)
            ->when($parentId, fn ($query) => $query->where('parent_block_id', $parentId), fn ($query) => $query->whereNull('parent_block_id'))
            ->where('position', '>=', $insertPosition)
            ->increment('position');

        $block = Block::create([
            'content_id' => $this->content->id,
            'parent_block_id' => $parentId,
            'type' => $blockType->key,
            'position' => $insertPosition,
            'data' => $this->getDefaultDataForBlockType($blockType, $this->addBlockColumnIndex),
        ]);

        $this->addBlockParentId = null;
        $this->addBlockColumnIndex = null;
        $this->addBlockPosition = null;
        $this->blockLibraryOpen = false;
        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();

        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'subject_type' => Block::class,
            'subject_id' => $block->id,
        ]);
    }

    public function addBlockAbove($blockId)
    {
        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $this->addBlockPosition = 'above';
        $this->addBlockParentId = $block->parent_block_id;
        $this->addBlockColumnIndex = $block->data['_column'] ?? null;
        $this->selectedBlockId = $blockId;
        $this->blockLibraryOpen = true;
    }

    public function addBlockBelow($blockId)
    {
        $block = Block::where('content_id', $this->content->id)->findOrFail($blockId);
        $this->addBlockPosition = 'below';
        $this->addBlockParentId = $block->parent_block_id;
        $this->addBlockColumnIndex = $block->data['_column'] ?? null;
        $this->selectedBlockId = $blockId;
        $this->blockLibraryOpen = true;
    }

    public function addNestedBlock($parentBlockId, $columnIndex = null): void
    {
        $parentBlock = Block::where('content_id', $this->content->id)->findOrFail($parentBlockId);

        if (! $this->blockCanContainBlocks($parentBlock->type)) {
            return;
        }

        $this->addBlockParentId = $parentBlock->id;
        $this->addBlockColumnIndex = $columnIndex !== null ? (int) $columnIndex : null;
        $this->addBlockPosition = 'inside';
        $this->selectedBlockId = $parentBlock->id;
        $this->blockLibraryOpen = true;
    }

    public function duplicateBlock($blockId)
    {
        $original = Block::findOrFail($blockId);
        $blockType = BlockType::where('key', $original->type)->firstOrFail();

        $newPosition = $original->position + 1;
        Block::where('content_id', $this->content->id)
            ->when($original->parent_block_id, fn ($query) => $query->where('parent_block_id', $original->parent_block_id), fn ($query) => $query->whereNull('parent_block_id'))
            ->where('position', '>=', $newPosition)
            ->increment('position');

        $block = Block::create([
            'content_id' => $this->content->id,
            'parent_block_id' => $original->parent_block_id,
            'type' => $original->type,
            'position' => $newPosition,
            'data' => $original->data,
        ]);

        $this->loadBlocks();
        $this->selectedBlockId = $block->id;
        $this->markSaved();
    }

    public function updateContent($field, $value)
    {
        // Auto-generate slug when name changes and slug hasn't been manually edited
        if ($field === 'name' && $this->content->slug === Str::slug($this->content->name)) {
            $this->content->update([
                'name' => $value,
                'slug' => Str::slug($value),
                'updated_by' => auth()->id(),
            ]);
        } elseif ($field === 'parent_id') {
            $this->content->update([
                'parent_id' => $value ?: null,
                'updated_by' => auth()->id(),
            ]);
        } elseif ($field === 'status') {
            $this->content->update([
                'status' => $value,
                'published_at' => $value === 'published' && ! $this->content->published_at ? now() : $this->content->published_at,
                'updated_by' => auth()->id(),
            ]);
        } else {
            $this->content->update([
                $field => $value,
                'updated_by' => auth()->id(),
            ]);
        }
        $this->content->refresh();
        $this->markSaved();
    }

    public function getFoldersProperty()
    {
        return Content::where('space_id', $this->content->space_id)
            ->where('type', 'folder')
            ->where('id', '!=', $this->content->id)
            ->orderBy('name')
            ->get();
    }

    public function updateContentMeta($key, $value)
    {
        $meta = $this->content->meta ?? [];
        $meta[$key] = $value;
        $this->content->update([
            'meta' => $meta,
            'updated_by' => auth()->id(),
        ]);
        $this->content->refresh();
        $this->markSaved();
    }

    public function handleBlockUpdated($blockId = null, $fieldKey = null, $value = null)
    {
        if ($blockId === null) {
            return;
        }
        if (is_array($blockId)) {
            $fieldKey = $blockId['fieldKey'] ?? $blockId[1] ?? null;
            $value = $blockId['value'] ?? $blockId[2] ?? null;
            $blockId = $blockId['blockId'] ?? $blockId[0] ?? null;
        }
        if ($blockId !== null && $fieldKey !== null) {
            $this->updateBlock($blockId, $fieldKey, $value);
        }
    }

    public function updateBlock($blockId, $fieldKey, $value)
    {
        $block = Block::findOrFail($blockId);
        $data = $block->data ?? [];
        $data[$fieldKey] = $value;
        $block->update(['data' => $data]);

        $this->content->touch();
        $this->content->update(['updated_by' => auth()->id()]);

        $this->loadBlocks();
        $this->selectedBlockId = $blockId;
        $this->markSaved();
    }

    public function deleteBlock($blockId)
    {
        $block = Block::findOrFail($blockId);
        $position = $block->position;
        $block->delete();

        Block::where('content_id', $this->content->id)
            ->when($block->parent_block_id, fn ($query) => $query->where('parent_block_id', $block->parent_block_id), fn ($query) => $query->whereNull('parent_block_id'))
            ->where('position', '>', $position)
            ->decrement('position');

        $this->loadBlocks();
        $this->selectedBlockId = $this->blocks[0]['id'] ?? null;
        $this->markSaved();
    }

    public function sortItem($itemId, $position)
    {
        $blockIds = array_column($this->blocks, 'id');
        $currentIndex = array_search((int) $itemId, $blockIds);

        if ($currentIndex === false) {
            return;
        }

        array_splice($blockIds, $currentIndex, 1);
        array_splice($blockIds, $position, 0, [(int) $itemId]);

        foreach ($blockIds as $index => $blockId) {
            Block::where('id', $blockId)->update(['position' => $index]);
        }

        $this->content->touch();
        $this->content->update(['updated_by' => auth()->id()]);
        $this->loadBlocks();
        $this->markSaved();
    }

    public function publish()
    {
        $this->content->update([
            'status' => 'published',
            'published_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        $this->createRevision('Published');

        Activity::create([
            'space_id' => $this->content->space_id,
            'user_id' => auth()->id(),
            'action' => 'published',
            'subject_type' => Content::class,
            'subject_id' => $this->content->id,
        ]);

        $this->dispatch('published');
    }

    public function unpublish()
    {
        $this->content->update([
            'status' => 'draft',
            'published_at' => null,
            'updated_by' => auth()->id(),
        ]);
        $this->content->refresh();
    }

    public function createRevision(?string $label = null): void
    {
        ContentRevision::create([
            'content_id' => $this->content->id,
            'user_id' => auth()->id(),
            'snapshot' => $this->getContentSnapshot(),
            'label' => $label,
        ]);
    }

    public function restoreRevision($revisionId): void
    {
        $revision = ContentRevision::where('content_id', $this->content->id)->findOrFail($revisionId);
        $snapshot = $revision->snapshot;

        // Restore content fields
        if (isset($snapshot['content'])) {
            $this->content->update(array_merge($snapshot['content'], ['updated_by' => auth()->id()]));
        }

        // Restore blocks
        if (isset($snapshot['blocks'])) {
            Block::where('content_id', $this->content->id)->delete();
            foreach ($snapshot['blocks'] as $index => $blockSnapshot) {
                $this->restoreSnapshotBlock($blockSnapshot, null, $index);
            }
        }

        $this->loadBlocks();
        $this->content->refresh();
        $this->markSaved();
    }

    protected function getContentSnapshot(): array
    {
        $blocks = $this->content->blocks()
            ->with('children')
            ->orderBy('position')
            ->get();

        return [
            'content' => [
                'name' => $this->content->name,
                'slug' => $this->content->slug,
                'status' => $this->content->status,
                'meta' => $this->content->meta,
            ],
            'blocks' => $blocks->map(fn (Block $block): array => $this->snapshotBlock($block))->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshotBlock(Block $block): array
    {
        return [
            'type' => $block->type,
            'position' => $block->position,
            'data' => $block->data ?? [],
            'children' => $block->children
                ->sortBy('position')
                ->map(fn (Block $child): array => $this->snapshotBlock($child))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $blockSnapshot
     */
    protected function restoreSnapshotBlock(array $blockSnapshot, ?int $parentBlockId, int $fallbackPosition): Block
    {
        $block = Block::create([
            'content_id' => $this->content->id,
            'type' => $blockSnapshot['type'],
            'position' => $blockSnapshot['position'] ?? $fallbackPosition,
            'data' => $blockSnapshot['data'] ?? [],
            'parent_block_id' => $parentBlockId,
        ]);

        foreach ($blockSnapshot['children'] ?? [] as $index => $childSnapshot) {
            $this->restoreSnapshotBlock($childSnapshot, $block->id, $index);
        }

        return $block;
    }

    protected function markSaved(): void
    {
        $this->lastSavedAt = now();
        $this->savedJustNow = true;
        $this->previewVersion++;
        $this->dispatch('saved');
    }

    public function setSelectedBlockFromPreview(int $blockId): void
    {
        if (! $this->findBlockInTree($blockId)) {
            return;
        }

        $this->selectedBlockId = $blockId;
        $this->drawerOpen = true;
    }

    public function saveCheckpoint(): void
    {
        $this->createRevision('Manual save');
        $this->markSaved();
    }

    public function handleOpenAssetPicker($payload = null)
    {
        if ($payload === null) {
            return;
        }
        $fieldKey = is_array($payload) ? ($payload['fieldKey'] ?? $payload[0] ?? '') : $payload;
        $this->dispatch('open-asset-picker', fieldKey: $fieldKey)->to(\App\Livewire\Admin\Assets\AssetPickerModal::class);
    }

    public function handleAssetSelected($payload = null)
    {
        if ($payload === null) {
            return;
        }
        $fieldKey = $payload['fieldKey'] ?? $payload[0] ?? null;
        $asset = $payload['asset'] ?? $payload[1] ?? null;

        if ($fieldKey && $asset && $this->selectedBlockId) {
            $url = is_array($asset) ? ($asset['url'] ?? '') : ($asset->url ?? '');
            $url = Asset::toRelativeUrl($url);
            $this->updateBlock($this->selectedBlockId, $fieldKey, $url);

            if (is_array($asset)) {
                if (isset($asset['focal_x'])) {
                    $this->updateBlock($this->selectedBlockId, $fieldKey.'_focal_x', (float) $asset['focal_x']);
                }

                if (isset($asset['focal_y'])) {
                    $this->updateBlock($this->selectedBlockId, $fieldKey.'_focal_y', (float) $asset['focal_y']);
                }
            }
        }
    }

    public function getContentTreeProperty()
    {
        $space = $this->content->space;
        if (! $space) {
            return collect();
        }

        return Content::where('space_id', $space->id)
            ->whereNull('parent_id')
            ->orderBy('type')
            ->orderBy('name')
            ->with('children')
            ->get();
    }

    public function getBreadcrumbsProperty()
    {
        $crumbs = [];
        $current = $this->content->parent;
        while ($current) {
            array_unshift($crumbs, $current);
            $current = $current->parent;
        }

        return $crumbs;
    }

    public function getRevisionsProperty()
    {
        return $this->content->revisions()->with('user')->take(20)->get();
    }

    public function getSelectedBlockProperty(): ?array
    {
        return $this->selectedBlockId ? $this->findBlockInTree((int) $this->selectedBlockId) : null;
    }

    public function getPreviewUrlProperty(): string
    {
        return PreviewController::signedUrl($this->content);
    }

    public function getPreviewFrameUrlProperty(): string
    {
        return route('admin.content.preview', ['content' => $this->content]).'?v='.$this->previewVersion;
    }

    protected function getDefaultDataForBlockType($blockType, ?int $columnIndex = null): array
    {
        $data = [];
        foreach ($blockType->schema['fields'] ?? [] as $field) {
            $data[$field['key']] = $field['default'] ?? '';
            if (($field['translatable'] ?? false)) {
                $data[$field['key']] = ['en' => $data[$field['key']]];
            }
            if (($field['type'] ?? '') === 'repeater') {
                $data[$field['key']] = [];
            }
        }

        if ($columnIndex !== null) {
            $data['_column'] = $columnIndex;
        }

        return $data;
    }

    protected function findBlockInTree(int $blockId, ?array $blocks = null): ?array
    {
        foreach ($blocks ?? $this->blocks as $block) {
            if ((int) $block['id'] === $blockId) {
                return $block;
            }

            $child = $this->findBlockInTree($blockId, $block['children'] ?? []);

            if ($child) {
                return $child;
            }
        }

        return null;
    }

    protected function blockCanContainBlocks(string $blockTypeKey): bool
    {
        $blockType = $this->blockTypes[$blockTypeKey] ?? BlockType::where('key', $blockTypeKey)->first();

        return (bool) ($blockType?->schema['can_contain_blocks'] ?? false);
    }

    public function render()
    {
        return view('livewire.admin.content.editor')
            ->layout('layouts.admin');
    }
}
