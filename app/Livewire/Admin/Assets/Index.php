<?php

namespace App\Livewire\Admin\Assets;

use App\Models\Asset;
use App\Models\AssetFolder;
use App\Models\AssetTag;
use App\Models\Space;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public $spaceId = null;

    public $folderId = null;

    public $uploadFiles = [];

    public $showUploadModal = false;

    public $showDetailSlideOver = false;

    public $selectedAssetId = null;

    public $showNewFolderModal = false;

    public $newFolderName = '';

    public $sortBy = 'created_at';

    public $sortDir = 'desc';

    // Edit form (for slide-over)
    public $editDisplayName = '';

    public $editFolderId = null;

    public $editTags = '';

    public float $editFocalX = 50.0;

    public float $editFocalY = 50.0;

    public function mount()
    {
        $space = Space::first();
        $this->spaceId = $space?->id;
    }

    public function uploadAssets()
    {
        if (! $this->spaceId) {
            $this->addError('uploadFiles', 'No space available. Create a space first.');

            return;
        }

        if (empty($this->uploadFiles)) {
            $this->addError('uploadFiles', 'Please select at least one file to upload.');

            return;
        }

        $this->validate([
            'uploadFiles.*' => 'file|max:51200', // 50MB max for videos
        ]);

        // Ensure the assets directory exists on the public disk
        Storage::disk('public')->makeDirectory('assets');

        foreach ($this->uploadFiles as $file) {
            $path = $file->store('assets', 'public');

            if ($path === false) {
                $this->addError('uploadFiles', 'Failed to store file: '.$file->getClientOriginalName());

                continue;
            }

            Asset::create([
                'space_id' => $this->spaceId,
                'folder_id' => $this->folderId ?: null,
                'disk' => 'public',
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => null,
                'height' => null,
            ]);
        }

        $this->uploadFiles = [];
        $this->showUploadModal = false;

        return $this->redirect(route('admin.assets.index'), navigate: true);
    }

    public function openAssetDetail($assetId)
    {
        $asset = Asset::with('tags', 'folder')->findOrFail($assetId);
        $this->selectedAssetId = $assetId;
        $this->editDisplayName = $asset->display_name ?? $asset->filename;
        $this->editFolderId = $asset->folder_id;
        $this->editTags = $asset->tags->pluck('name')->join(', ');
        $this->editFocalX = $asset->focalX();
        $this->editFocalY = $asset->focalY();
        $this->showDetailSlideOver = true;
    }

    public function closeAssetDetail()
    {
        $this->showDetailSlideOver = false;
        $this->selectedAssetId = null;
    }

    public function saveAssetDetails()
    {
        $asset = Asset::findOrFail($this->selectedAssetId);

        $asset->update([
            'display_name' => $this->editDisplayName ?: null,
            'folder_id' => $this->editFolderId ?: null,
            'focal_x' => $this->editFocalX,
            'focal_y' => $this->editFocalY,
        ]);

        // Sync tags
        $space = Space::find($this->spaceId);
        $tagNames = array_filter(array_map('trim', explode(',', $this->editTags)));
        $tags = AssetTag::findOrCreateFromNames($space, $tagNames);
        $asset->tags()->sync(collect($tags)->pluck('id'));

        $this->closeAssetDetail();
    }

    public function setFocalPoint(float $x, float $y): void
    {
        $this->editFocalX = max(0.0, min(100.0, round($x, 2)));
        $this->editFocalY = max(0.0, min(100.0, round($y, 2)));
    }

    public function createFolder()
    {
        $this->validate(['newFolderName' => 'required|string|max:255']);

        AssetFolder::create([
            'space_id' => $this->spaceId,
            'parent_id' => null,
            'name' => $this->newFolderName,
        ]);

        $this->newFolderName = '';
        $this->showNewFolderModal = false;
    }

    public function deleteAsset($id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->hasConfiguredDisk()) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();
        $this->closeAssetDetail();
    }

    public function selectFolder($folderId)
    {
        $this->folderId = ($folderId === null || $folderId === 'null' || $folderId === '') ? null : $folderId;
    }

    public function setSort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function render()
    {
        $space = Space::find($this->spaceId);
        $folder = $this->folderId ? AssetFolder::find($this->folderId) : null;

        $assetsQuery = Asset::with('tags', 'folder')
            ->where('space_id', $this->spaceId)
            ->when($this->folderId !== null && $this->folderId !== '', fn ($q) => $q->where('folder_id', $this->folderId));

        $assets = match ($this->sortBy) {
            'filename' => $assetsQuery->orderBy('filename', $this->sortDir)->get(),
            'size' => $assetsQuery->orderBy('size', $this->sortDir)->get(),
            default => $assetsQuery->orderBy('created_at', $this->sortDir)->get(),
        };

        $folders = AssetFolder::where('space_id', $this->spaceId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allFolders = AssetFolder::where('space_id', $this->spaceId)
            ->orderBy('name')
            ->get();

        $selectedAsset = $this->selectedAssetId ? Asset::with('tags', 'folder')->find($this->selectedAssetId) : null;

        return view('livewire.admin.assets.index', [
            'space' => $space,
            'folder' => $folder,
            'assets' => $assets,
            'folders' => $folders,
            'allFolders' => $allFolders,
            'selectedAsset' => $selectedAsset,
        ])->layout('layouts.admin');
    }
}
