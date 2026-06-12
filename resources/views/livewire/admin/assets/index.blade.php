<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Assets</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage media and files</p>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">
    <main class="flex-1 min-w-0 overflow-hidden">
<div class="flex flex-col lg:flex-row h-full">
    <!-- Left Sidebar: Folders (horizontal on mobile, vertical on desktop) -->
    <div class="lg:w-64 shrink-0 border-b lg:border-b-0 lg:border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 overflow-x-auto lg:overflow-x-visible lg:overflow-y-auto">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between gap-2">
            <flux:heading size="md">Folders</flux:heading>
            <flux:button wire:click="$set('showNewFolderModal', true)" variant="ghost" size="sm" title="New folder">
                <flux:icon.folder-plus class="size-4" />
            </flux:button>
        </div>
        <div class="p-3 flex lg:flex-col gap-0.5">
            <button
                wire:click="selectFolder(null)"
                class="shrink-0 lg:w-full text-left px-3 py-2.5 rounded-lg transition-colors duration-150 hover:bg-zinc-200 dark:hover:bg-zinc-800 {{ $folderId === null ? 'bg-zinc-200 dark:bg-zinc-800 font-medium' : '' }}"
            >
                <div class="flex items-center gap-2">
                    <flux:icon.folder class="size-4" />
                    <span>All Assets</span>
                </div>
            </button>
            @foreach($folders as $f)
                <button
                    wire:click="selectFolder({{ $f->id }})"
                    class="shrink-0 lg:w-full text-left px-3 py-2.5 rounded-lg transition-colors duration-150 hover:bg-zinc-200 dark:hover:bg-zinc-800 {{ $folderId === $f->id ? 'bg-zinc-200 dark:bg-zinc-800 font-medium' : '' }}"
                >
                    <div class="flex items-center gap-2">
                        <flux:icon.folder class="size-4" />
                        <span>{{ $f->name }}</span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-y-auto min-w-0">
        <div class="p-6 md:p-8">
            <div class="mb-6">
                <flux:heading>
                    @if($folder)
                        {{ $folder->name }}
                    @else
                        All Assets
                    @endif
                </flux:heading>
                <flux:text class="text-muted-foreground text-sm mt-1">Images, videos, and documents for your content</flux:text>
            </div>
            <div class="flex flex-col gap-4 mb-8 pb-6 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search assets, tags, credit..." icon="magnifying-glass" class="sm:max-w-xs" />
                        <flux:select wire:model.live="typeFilter" class="sm:max-w-44">
                            <option value="all">All types</option>
                            <option value="images">Images</option>
                            <option value="videos">Videos</option>
                            <option value="documents">Documents</option>
                            <option value="expired">Expired rights</option>
                        </flux:select>
                    </div>
                    <flux:button wire:click="$set('showUploadModal', true)" variant="primary" size="sm">
                        <flux:icon.arrow-up-tray class="size-4" />
                        Upload
                    </flux:button>
                </div>

                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Sort:</span>
                    <button wire:click="setSort('created_at')" class="px-3 py-1.5 rounded-lg transition-colors duration-150 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $sortBy === 'created_at' ? 'font-medium bg-zinc-100 dark:bg-zinc-800' : '' }}">Date</button>
                    <button wire:click="setSort('filename')" class="px-3 py-1.5 rounded-lg transition-colors duration-150 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $sortBy === 'filename' ? 'font-medium bg-zinc-100 dark:bg-zinc-800' : '' }}">Name</button>
                    <button wire:click="setSort('size')" class="px-3 py-1.5 rounded-lg transition-colors duration-150 hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $sortBy === 'size' ? 'font-medium bg-zinc-100 dark:bg-zinc-800' : '' }}">Size</button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 sm:gap-5" wire:key="assets-grid-{{ $assets->count() }}-{{ $assets->max('id') ?? 0 }}">
                @forelse($assets as $asset)
                    <div
                        wire:click="openAssetDetail({{ $asset->id }})"
                        class="group relative cursor-pointer"
                    >
                        <flux:card class="overflow-hidden hover:ring-2 hover:ring-primary-500 hover:shadow-lg transition-all duration-200">
                            {{-- Preview: Image --}}
                            @if($asset->isImage())
                                <img
                                    src="{{ $asset->url() }}"
                                    alt="{{ $asset->displayName() }}"
                                    class="w-full aspect-square object-cover"
                                    loading="lazy"
                                />
                            {{-- Preview: Video --}}
                            @elseif($asset->isVideo())
                                <div class="w-full aspect-square bg-zinc-900 relative overflow-hidden flex items-center justify-center">
                                    <video
                                        src="{{ $asset->url() }}"
                                        class="max-w-full max-h-full object-contain"
                                        muted
                                        preload="metadata"
                                    ></video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 pointer-events-none">
                                        <flux:icon.play-circle class="size-14 text-white/90 drop-shadow-lg" />
                                    </div>
                                </div>
                            {{-- Preview: Document --}}
                            @else
                                <div class="w-full aspect-square bg-zinc-100 dark:bg-zinc-800 flex flex-col items-center justify-center p-4">
                                    <flux:icon.document class="size-16 text-muted-foreground" />
                                    <span class="text-xs text-muted-foreground mt-2 truncate max-w-full px-2">{{ pathinfo($asset->filename, PATHINFO_EXTENSION) }}</span>
                                </div>
                            @endif
                            <div class="p-3">
                                <flux:text class="text-sm font-medium truncate block" title="{{ $asset->displayName() }}">{{ $asset->displayName() }}</flux:text>
                                <flux:text class="text-xs text-muted-foreground">
                                    {{ $asset->size >= 1048576 ? number_format($asset->size / 1048576, 1) . ' MB' : number_format($asset->size / 1024, 1) . ' KB' }}
                                </flux:text>
                                @if($asset->dimensions())
                                    <flux:text class="text-xs text-muted-foreground">{{ $asset->dimensions() }}</flux:text>
                                @endif
                                @if($asset->isExpired())
                                    <span class="mt-1 inline-flex rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800">Expired</span>
                                @endif
                                @if($asset->tags->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($asset->tags->take(2) as $tag)
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-200 dark:bg-zinc-700 text-muted-foreground">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </flux:card>
                    </div>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-16 px-4">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                            <flux:icon.photo class="size-7 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <flux:heading size="md" class="mt-4">No assets in this folder</flux:heading>
                        <flux:text class="mt-2 text-center text-sm text-muted-foreground max-w-sm">Upload images, videos, or documents to use in your content.</flux:text>
                        <flux:button wire:click="$set('showUploadModal', true)" variant="primary" class="mt-6">
                            <flux:icon.arrow-up-tray class="size-4" />
                            Upload assets
                        </flux:button>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
</main>

    {{-- Right aside: Details --}}
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0"><h2 class="text-sm font-bold text-slate-800">Details</h2></div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500 flex items-center justify-center"><p>Select an asset.</p></div>
    </aside>
    </div>

{{-- Asset Detail Slide-over --}}
@if($showDetailSlideOver && $selectedAsset)
<div
    x-data="{ open: @entangle('showDetailSlideOver') }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" wire:click="closeAssetDetail"></div>

    {{-- Panel --}}
    <div
        class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-white dark:bg-zinc-800 shadow-2xl flex flex-col"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
    >
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between shrink-0">
            <flux:heading size="md">Asset Details</flux:heading>
            <button wire:click="closeAssetDetail" class="p-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <flux:icon.x-mark class="size-5" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-6">
            {{-- Preview --}}
            <div class="rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-900 aspect-square max-h-64">
                @if($selectedAsset->isImage())
                    <div
                        class="relative h-full w-full cursor-crosshair"
                        x-on:click="
                            const rect = $el.getBoundingClientRect();
                            const x = ((($event.clientX - rect.left) / rect.width) * 100);
                            const y = ((($event.clientY - rect.top) / rect.height) * 100);
                            $wire.call('setFocalPoint', x, y);
                        "
                    >
                        <img
                            src="{{ $selectedAsset->url() }}"
                            alt="{{ $selectedAsset->displayName() }}"
                            class="w-full h-full object-cover"
                            style="object-position: {{ $editFocalX }}% {{ $editFocalY }}%;"
                        />
                        <div class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $editFocalX }}%; top: {{ $editFocalY }}%;">
                            <div class="h-4 w-4 rounded-full border-2 border-white bg-teal-500 shadow"></div>
                        </div>
                    </div>
                @elseif($selectedAsset->isVideo())
                    <video src="{{ $selectedAsset->url() }}" controls class="w-full h-full object-contain"></video>
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <flux:icon.document class="size-24 text-muted-foreground" />
                    </div>
                @endif
            </div>

            @if($selectedAsset->isImage())
            <flux:field>
                <flux:label>Focal Point</flux:label>
                <flux:description>Click the image preview to set the image focus used by blocks on the website.</flux:description>
                <div class="mt-2 text-xs text-slate-500">X: {{ number_format($editFocalX, 1) }}% · Y: {{ number_format($editFocalY, 1) }}%</div>
            </flux:field>
            @endif

            {{-- Display Name --}}
            <flux:field>
                <flux:label>Display Name</flux:label>
                <flux:input wire:model="editDisplayName" placeholder="Custom name for this asset" />
                <flux:error name="editDisplayName" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="editDescription" rows="3" placeholder="Internal notes, campaign context, or usage guidance" />
                <flux:error name="editDescription" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Alt Text</flux:label>
                    <flux:input wire:model="editAlt" placeholder="Describe the asset" />
                    <flux:error name="editAlt" />
                </flux:field>

                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="editTitle" placeholder="Optional public title" />
                    <flux:error name="editTitle" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Credit</flux:label>
                    <flux:input wire:model="editCredit" placeholder="Photographer or source" />
                    <flux:error name="editCredit" />
                </flux:field>

                <flux:field>
                    <flux:label>License</flux:label>
                    <flux:input wire:model="editLicense" placeholder="Owned, stock, CC BY..." />
                    <flux:error name="editLicense" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Copyright</flux:label>
                <flux:input wire:model="editCopyright" placeholder="Copyright owner or rights note" />
                <flux:error name="editCopyright" />
            </flux:field>

            <flux:field>
                <flux:label>Source URL</flux:label>
                <flux:input wire:model="editSourceUrl" placeholder="https://..." />
                <flux:error name="editSourceUrl" />
            </flux:field>

            <flux:field>
                <flux:label>Rights Expiration</flux:label>
                <flux:input type="date" wire:model="editExpiresAt" />
                <flux:error name="editExpiresAt" />
            </flux:field>

            {{-- Tags --}}
            <flux:field>
                <flux:label>Tags</flux:label>
                <flux:input wire:model="editTags" placeholder="tag1, tag2, tag3" />
                <flux:description>Comma-separated tags for organizing</flux:description>
            </flux:field>

            {{-- Folder --}}
            <flux:field>
                <flux:label>Folder</flux:label>
                <flux:select wire:model="editFolderId">
                    <option value="">Root (no folder)</option>
                    @foreach($allFolders as $f)
                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            {{-- Link --}}
            <flux:field>
                <flux:label>Asset URL</flux:label>
                <div class="flex gap-2" x-data="{ copied: false, url: {{ \Illuminate\Support\Js::from($selectedAsset->relativeUrl()) }} }">
                    <flux:input value="{{ $selectedAsset->relativeUrl() }}" readonly class="font-mono text-sm" />
                    <flux:button
                        type="button"
                        x-on:click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        variant="ghost"
                        size="sm"
                        x-bind:title="copied ? 'Copied!' : 'Copy link'"
                    >
                        <flux:icon.clipboard class="size-5" x-show="!copied" />
                        <flux:icon.check class="size-5 text-green-600" x-show="copied" x-cloak />
                    </flux:button>
                </div>
                <flux:description>Use this URL to reference the asset</flux:description>
            </flux:field>

            {{-- Meta --}}
            <div class="text-sm text-muted-foreground space-y-1">
                <div>Filename: {{ $selectedAsset->filename }}</div>
                <div>Size: {{ $selectedAsset->size >= 1048576 ? number_format($selectedAsset->size / 1048576, 1) . ' MB' : number_format($selectedAsset->size / 1024, 1) . ' KB' }}</div>
                @if($selectedAsset->dimensions())
                    <div>Dimensions: {{ $selectedAsset->dimensions() }}</div>
                @endif
                <div>Type: {{ $selectedAsset->mime }}</div>
                @if($selectedAsset->checksum)
                    <div class="break-all">Checksum: {{ $selectedAsset->checksum }}</div>
                @endif
                @if($selectedAsset->expires_at)
                    <div>Rights expire: {{ $selectedAsset->expires_at->toFormattedDateString() }}</div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-800 dark:border-zinc-700 dark:text-zinc-100">
                    Used in {{ $selectedAssetUsage->count() }} {{ \Illuminate\Support\Str::plural('place', $selectedAssetUsage->count()) }}
                </div>
                <div class="max-h-48 overflow-y-auto divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($selectedAssetUsage as $usage)
                        <a href="{{ route('admin.content.edit', $usage['content']) }}" wire:navigate class="block px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-900">
                            <div class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $usage['content']->name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ $usage['block'] ? 'Block: '.$usage['block']->type : 'Content meta' }} / {{ $usage['location'] }}
                            </div>
                        </a>
                    @empty
                        <div class="px-3 py-4 text-sm text-muted-foreground">No current content references found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between shrink-0">
            <div>
                <flux:button wire:click="deleteAsset({{ $selectedAsset->id }})" wire:confirm="Delete this asset? The file will be permanently removed." variant="danger" size="sm">
                    <flux:icon.trash class="size-4" />
                    Delete
                </flux:button>
                <flux:error name="deleteAsset" />
            </div>
            <div class="flex gap-2">
                <flux:button wire:click="closeAssetDetail" variant="ghost">Cancel</flux:button>
                <flux:button wire:click="saveAssetDetails" variant="primary">Save</flux:button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Upload Modal --}}
<flux:modal wire:model="showUploadModal">
    <flux:heading size="lg">Upload Assets</flux:heading>
    <form wire:submit="uploadAssets" class="mt-4 space-y-4">
        <flux:field>
            <flux:label>Files</flux:label>
            <input type="file" wire:model="uploadFiles" multiple class="block w-full text-sm text-zinc-500 file:me-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-100 file:text-zinc-800 dark:file:bg-zinc-700 dark:file:text-zinc-200 file:cursor-pointer">
            <flux:error name="uploadFiles.*" />
            <flux:description>Photos, videos, and documents. Max 50MB per file. Wait for files to finish uploading before clicking Upload.</flux:description>
        </flux:field>

        @if($uploadFiles)
            <div class="space-y-2 max-h-32 overflow-y-auto">
                @foreach($uploadFiles as $file)
                    <flux:text class="text-sm">{{ $file->getClientOriginalName() }}</flux:text>
                @endforeach
            </div>
        @endif

        <div class="flex justify-end gap-3">
            <flux:button type="button" wire:click="$set('showUploadModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Upload</span>
                <span wire:loading>Uploading...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>

{{-- New Folder Modal --}}
<flux:modal wire:model="showNewFolderModal">
    <flux:heading size="lg">New Folder</flux:heading>
    <form wire:submit="createFolder" class="mt-4 space-y-4">
        <flux:field>
            <flux:label>Folder Name</flux:label>
            <flux:input wire:model="newFolderName" placeholder="My folder" />
            <flux:error name="newFolderName" />
        </flux:field>
        <div class="flex justify-end gap-3">
            <flux:button type="button" wire:click="$set('showNewFolderModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button type="submit" variant="primary">Create</flux:button>
        </div>
    </form>
</flux:modal>
</div>
