<div class="cms-shell flex h-full w-full min-w-0 flex-col">
    <header class="cms-topbar" aria-label="Page header">
        <div class="min-w-0">
            <h1 class="cms-title">Content</h1>
            <p class="cms-subtitle">Pages, folders and global content for your site.</p>
        </div>

        <div class="cms-actions">
            @can('create content')
                <a href="{{ route('admin.content.create', ['type' => 'folder', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="cms-btn cms-btn-secondary">
                    <i class="ph ph-folder-plus" aria-hidden="true"></i>
                    New folder
                </a>
                <a href="{{ route('admin.content.create', ['type' => 'page', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="cms-btn cms-btn-primary">
                    <i class="ph ph-plus" aria-hidden="true"></i>
                    New page
                </a>
            @endcan
        </div>
    </header>

    <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px]">
        <main class="min-w-0 overflow-y-auto">
            <div class="flex min-h-full flex-col gap-6 p-[var(--pad-view)]">
                @php $stats = $this->stats; @endphp
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($stats['total']) }}</div>
                                <div class="cms-subtitle">Total stories</div>
                            </div>
                            <span class="cms-tile cms-tile-info"><i class="ph-fill ph-files" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($stats['published']) }}</div>
                                <div class="cms-subtitle">Published</div>
                            </div>
                            <span class="cms-tile text-success bg-success-subtle"><i class="ph-fill ph-check-circle" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($stats['drafts']) }}</div>
                                <div class="cms-subtitle">Drafts</div>
                            </div>
                            <span class="cms-tile text-warning bg-warning-subtle"><i class="ph-fill ph-pencil-simple" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ $stats['languages'] }}</div>
                                <div class="cms-subtitle">Languages</div>
                            </div>
                            <span class="cms-tile cms-tile-accent"><i class="ph-fill ph-globe" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </div>

                <div class="cms-panel">
                    <div class="cms-toolbar">
                        <label class="cms-input w-52">
                            <i class="ph ph-magnifying-glass text-tertiary" aria-hidden="true"></i>
                            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search content" />
                        </label>

                        <div class="cms-seg" aria-label="Content type filter">
                            <button type="button" wire:click="setTypeFilter('all')" class="cms-seg-btn" data-active="{{ $typeFilter === 'all' ? 'true' : 'false' }}">All</button>
                            <button type="button" wire:click="setTypeFilter('page')" class="cms-seg-btn" data-active="{{ $typeFilter === 'page' ? 'true' : 'false' }}">Pages</button>
                            <button type="button" wire:click="setTypeFilter('folder')" class="cms-seg-btn" data-active="{{ $typeFilter === 'folder' ? 'true' : 'false' }}">Folders</button>
                            <button type="button" wire:click="setTypeFilter('global')" class="cms-seg-btn" data-active="{{ $typeFilter === 'global' ? 'true' : 'false' }}">Global</button>
                        </div>

                        <span class="flex-1"></span>

                        <div class="flex items-center gap-2">
                            <span class="text-2xs font-semibold uppercase tracking-[0.06em] text-tertiary">Sort</span>
                            <div class="relative">
                                <select wire:model.live="sortBy" class="cms-select">
                                    <option value="updated_at">Last updated</option>
                                    <option value="name">Name</option>
                                    <option value="created_at">Created</option>
                                    <option value="status">Status</option>
                                </select>
                                <i class="ph ph-caret-down pointer-events-none absolute right-2 top-1.5 text-tertiary" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>

                    <div class="min-w-[720px]">
                        <div class="cms-table-head">
                            <div><input type="checkbox" class="rounded border-strong text-accent focus:ring-accent" disabled /></div>
                            <div>Name</div>
                            <div>Type</div>
                            <div>Updated</div>
                            <div>Status</div>
                        </div>

                        @forelse($this->contentTree as $row)
                            @php $content = $row->content; $depth = $row->depth; @endphp
                            <div class="cms-table-row group" wire:key="content-{{ $content->id }}">
                                <div><input type="checkbox" class="rounded border-strong text-accent focus:ring-accent" /></div>

                                <div class="flex min-w-0 items-center gap-2" style="padding-left: {{ $depth * 20 }}px;">
                                    @if($content->isFolder())
                                        <button type="button" wire:click="toggleFolder({{ $content->id }})" class="cms-iconbtn !h-5 !w-5" aria-label="{{ $this->isFolderExpanded($content->id) ? 'Collapse' : 'Expand' }}">
                                            <i class="ph {{ $this->isFolderExpanded($content->id) ? 'ph-caret-down' : 'ph-caret-right' }} text-xs" aria-hidden="true"></i>
                                        </button>
                                        <span class="cms-tile cms-tile-info"><i class="ph-fill ph-folder" aria-hidden="true"></i></span>
                                    @else
                                        <span class="w-5 shrink-0" aria-hidden="true"></span>
                                        <span class="cms-tile"><i class="ph ph-file-text" aria-hidden="true"></i></span>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        @if($content->isFolder())
                                            <span class="block truncate text-sm font-medium text-primary">{{ $content->name }}</span>
                                        @else
                                            <a href="{{ route('admin.content.editor', $content) }}" wire:navigate class="block truncate text-sm font-medium text-primary hover:text-accent-text">{{ $content->name }}</a>
                                        @endif
                                        <span class="block truncate font-mono text-2xs text-tertiary">/{{ $content->slug }}</span>
                                    </div>
                                </div>

                                <div>
                                    @if($content->isFolder())
                                        <span class="cms-badge cms-badge-info">Folder</span>
                                    @elseif($content->type === 'global')
                                        <span class="cms-badge cms-badge-accent">Global</span>
                                    @else
                                        <span class="cms-badge cms-badge-accent">Page</span>
                                    @endif
                                </div>

                                <div>
                                    <div class="text-sm text-secondary">{{ $content->updated_at->diffForHumans() }}</div>
                                    <div class="text-2xs text-tertiary">by {{ $content->updater?->name ?? $content->creator?->name ?? 'System' }}</div>
                                </div>

                                <div>
                                    <span class="cms-status">
                                        @if($content->status === 'published')
                                            <span class="cms-status-dot cms-status-dot-success"></span>
                                            Published
                                        @elseif($content->status === 'draft')
                                            <span class="cms-status-dot"></span>
                                            Draft
                                        @else
                                            <span class="cms-status-dot cms-status-dot-warning"></span>
                                            {{ ucfirst($content->status) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center px-4 py-20 text-center">
                                <div class="cms-tile !h-14 !w-14 !rounded-lg"><i class="ph ph-folder-open text-2xl" aria-hidden="true"></i></div>
                                <p class="mt-4 text-sm font-medium text-primary">No content found</p>
                                <p class="cms-subtitle">Get started by creating a folder or page.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-center gap-2 text-2xs text-tertiary">
                    <i class="ph ph-info" aria-hidden="true"></i>
                    Drag folders to reorder your content structure.
                </div>
            </div>
        </main>

        <aside class="cms-rail hidden xl:flex" aria-label="Space Activity">
            <div class="cms-rail-head">
                <i class="ph ph-activity text-tertiary" aria-hidden="true"></i>
                <h2 class="cms-rail-title">Space activity</h2>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse($this->recentActivity as $activity)
                    <div class="cms-rail-item">
                        <span class="cms-avatar">
                            @if($activity->user)
                                {{ strtoupper(substr($activity->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $activity->user->name)[1] ?? '', 0, 1)) }}
                            @else
                                API
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm leading-snug text-secondary">
                                <strong class="font-semibold text-primary">{{ $activity->user?->name ?? 'System' }}</strong>
                                {{ $activity->action }}
                                @if($activity->subject)
                                    @php
                                        $subjectName = $activity->subject->name ?? $activity->subject->key ?? class_basename($activity->subject_type);
                                        $subjectRoute = null;
                                        if ($activity->subject instanceof \App\Models\Content && $activity->subject->isPage()) {
                                            $subjectRoute = route('admin.content.editor', $activity->subject);
                                        }
                                    @endphp
                                    @if($subjectRoute)
                                        <a href="{{ $subjectRoute }}" wire:navigate class="font-medium text-accent-text hover:underline">{{ $subjectName }}</a>.
                                    @else
                                        <span class="font-medium text-accent-text">{{ $subjectName }}</span>.
                                    @endif
                                @endif
                            </p>
                            <time class="mt-1 block text-2xs text-tertiary">{{ $activity->created_at->diffForHumans() }}</time>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <i class="ph ph-clock-counter-clockwise text-2xl text-tertiary" aria-hidden="true"></i>
                        <p class="mt-2 text-xs text-tertiary">No recent activity</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
