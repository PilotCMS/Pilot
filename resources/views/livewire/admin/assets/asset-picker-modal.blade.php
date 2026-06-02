<flux:modal wire:model="show">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Select asset</flux:heading>
        <div class="flex items-center gap-2">
            <button wire:click="setViewMode('grid')" class="p-2 rounded {{ $viewMode === 'grid' ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}" title="Grid view">
                <flux:icon.squares-2x2 class="size-4" />
            </button>
            <button wire:click="setViewMode('list')" class="p-2 rounded {{ $viewMode === 'list' ? 'bg-zinc-100 dark:bg-zinc-800' : '' }}" title="List view">
                <flux:icon.list-bullet class="size-4" />
            </button>
        </div>
    </div>

    <div class="flex gap-4 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search assets..." icon="magnifying-glass" class="flex-1" />
    </div>

    <div class="flex gap-4 min-h-[300px]">
        {{-- Folders sidebar --}}
        <div class="w-48 shrink-0 border-r border-zinc-200 dark:border-zinc-700 pr-4">
            <button wire:click="selectFolder(null)" class="w-full text-left px-3 py-2 rounded-lg text-sm {{ $folderId === null ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                All
            </button>
            @foreach($folders as $folder)
                <button wire:click="selectFolder({{ $folder->id }})" class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2 {{ $folderId == $folder->id ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900' }}">
                    <flux:icon.folder class="size-4" />
                    {{ $folder->name }}
                </button>
            @endforeach
        </div>

        {{-- Assets grid/list --}}
        <div class="flex-1 overflow-y-auto max-h-[400px]">
            @if($viewMode === 'grid')
            <div class="grid grid-cols-4 gap-3">
                @foreach($assets as $asset)
                    <button wire:click="selectAsset({{ $asset->id }})" class="group text-left rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 hover:ring-2 hover:ring-primary-500 transition-all">
                        @if($asset->isImage())
                            <img src="{{ $asset->relativeUrl() }}" alt="{{ $asset->displayName() }}" class="w-full aspect-square object-cover" loading="lazy" />
                        @else
                            <div class="w-full aspect-square bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                                <flux:icon.document class="size-12 text-muted-foreground" />
                            </div>
                        @endif
                        <div class="p-2 truncate text-xs text-muted-foreground">{{ $asset->displayName() }}</div>
                    </button>
                @endforeach
            </div>
            @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($assets as $asset)
                    <button wire:click="selectAsset({{ $asset->id }})" class="w-full flex items-center gap-3 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-900 text-left">
                        @if($asset->isImage())
                            <img src="{{ $asset->relativeUrl() }}" alt="" class="w-12 h-12 object-cover rounded" />
                        @else
                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded flex items-center justify-center">
                                <flux:icon.document class="size-6 text-muted-foreground" />
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">{{ $asset->displayName() }}</div>
                            <div class="text-xs text-muted-foreground">{{ $asset->mime }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
            @endif

            @if($assets->isEmpty())
                <div class="py-12 text-center text-muted-foreground text-sm">
                    No assets found. Upload some in the Assets section.
                </div>
            @endif
        </div>
    </div>
</flux:modal>
