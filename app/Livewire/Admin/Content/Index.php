<?php

namespace App\Livewire\Admin\Content;

use App\Models\Activity;
use App\Models\Content;
use App\Models\Space;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $selectedFolderId = null;

    /** @var array<int> Folder IDs that are currently expanded in the tree */
    public $expandedFolderIds = [];

    public $search = '';

    public $typeFilter = 'all'; // all, page, folder, global

    public $sortBy = 'updated_at';

    public $sortDir = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => 'all', 'as' => 'type'],
        'sortBy' => ['except' => 'updated_at', 'as' => 'sort'],
        'sortDir' => ['except' => 'desc', 'as' => 'dir'],
    ];

    public function mount($folder = null)
    {
        $this->selectedFolderId = $folder;
    }

    public function toggleFolder($folderId)
    {
        $id = (int) $folderId;
        if (in_array($id, $this->expandedFolderIds, true)) {
            $this->expandedFolderIds = array_values(array_diff($this->expandedFolderIds, [$id]));
        } else {
            $this->expandedFolderIds = array_values(array_merge($this->expandedFolderIds, [$id]));
        }
    }

    public function isFolderExpanded($folderId): bool
    {
        return in_array((int) $folderId, $this->expandedFolderIds, true);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function setTypeFilter($type)
    {
        $this->typeFilter = $type;
        $this->resetPage();
    }

    public function setSort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'desc';
        }
    }

    public function selectFolder($folderId)
    {
        $this->selectedFolderId = $folderId;
        $this->resetPage();
    }

    public function getSpaceProperty()
    {
        return Space::first();
    }

    public function getStatsProperty()
    {
        $space = $this->space;
        if (! $space) {
            return ['total' => 0, 'published' => 0, 'drafts' => 0, 'languages' => 0];
        }

        $base = Content::where('space_id', $space->id);

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'drafts' => (clone $base)->where('status', 'draft')->count(),
            'languages' => max(1, $space->languages ?? 1),
        ];
    }

    public function getContentsProperty()
    {
        $space = $this->space;

        if (! $space) {
            return Content::query()->paginate(15);
        }

        $query = Content::where('space_id', $space->id);

        // Only filter by parent when not searching
        if (! $this->search) {
            $query->where('parent_id', $this->selectedFolderId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%");
            });
        }

        if ($this->typeFilter !== 'all') {
            if ($this->typeFilter === 'global') {
                $query->where('type', 'global');
            } else {
                $query->where('type', $this->typeFilter);
            }
        }

        $query->orderBy($this->sortBy, $this->sortDir);

        return $query->paginate(15);
    }

    /**
     * Tree of content for expandable folders: flat list of [content, depth] in tree order.
     * When search or typeFilter is set, only root-level (selectedFolderId) is used for compatibility.
     */
    public function getContentTreeProperty()
    {
        $space = $this->space;

        if (! $space) {
            return collect();
        }

        $all = Content::where('space_id', $space->id)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $byParent = $all->groupBy('parent_id');

        $list = [];
        $this->appendTreeRows(
            $byParent->get(null, collect())->values(),
            0,
            $byParent,
            $list
        );

        return collect($list);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Content>  $items
     * @param  array<int, object{content: Content, depth: int}>  $list
     */
    protected function appendTreeRows($items, int $depth, $byParent, array &$list): void
    {
        foreach ($items as $content) {
            $list[] = (object) ['content' => $content, 'depth' => $depth];

            if ($content->isFolder() && $this->isFolderExpanded($content->id)) {
                $children = $byParent->get($content->id, collect())->values();
                $this->appendTreeRows($children, $depth + 1, $byParent, $list);
            }
        }
    }

    public function getFoldersProperty()
    {
        $space = $this->space;

        if (! $space) {
            return collect();
        }

        return Content::where('space_id', $space->id)
            ->where('type', 'folder')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function getRecentActivityProperty()
    {
        $space = $this->space;

        if (! $space) {
            return collect();
        }

        return Activity::where('space_id', $space->id)
            ->with(['user', 'subject'])
            ->latest()
            ->take(10)
            ->get();
    }

    public function deleteContent($id)
    {
        $content = Content::findOrFail($id);

        if (! auth()->user()->can('delete content')) {
            $this->dispatch('error', message: 'You do not have permission to delete content.');

            return;
        }

        $content->delete();
        $this->dispatch('content-deleted');
    }

    public function render()
    {
        return view('livewire.admin.content.index')
            ->layout('layouts.admin');
    }
}
