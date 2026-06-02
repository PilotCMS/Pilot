<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    {{-- Fixed header: top 0, left 70px (after nav), right 500px (before aside) --}}
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Content Stories</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your website content structure and entries.</p>
        </div>
        @can('create content')
        <a href="{{ route('admin.content.create', ['type' => 'page']) }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 transition-colors">
            <i class="ph ph-plus"></i>
            New page
        </a>
        @endcan
    </header>

    <div class="flex flex-1 min-h-0">

    {{-- Main content: flex-1, fills space left of aside --}}
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-10">

        {{-- Stats cards --}}
        @php $stats = $this->stats; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                    <i class="ph-fill ph-files text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ number_format($stats['total']) }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Total Stories</div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center text-green-500 shrink-0">
                    <i class="ph-fill ph-check-circle text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ number_format($stats['published']) }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Published</div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-500 shrink-0">
                    <i class="ph-fill ph-pencil-simple text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ number_format($stats['drafts']) }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Drafts</div>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500 shrink-0">
                    <i class="ph-fill ph-globe text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-slate-900">{{ $stats['languages'] }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Languages</div>
                </div>
            </div>
        </div>

        {{-- Table card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Filter bar --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                <div class="flex items-center gap-1">
                    {{-- Search --}}
                    <div class="relative mr-3">
                        <i class="ph ph-funnel absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Filter" class="pl-8 pr-3 py-1.5 text-sm border border-slate-200 rounded-md bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none w-28 placeholder-slate-400" />
                    </div>

                    {{-- Type tabs --}}
                    <button wire:click="setTypeFilter('all')" class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $typeFilter === 'all' ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">All Content</button>
                    <button wire:click="setTypeFilter('page')" class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $typeFilter === 'page' ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">Pages</button>
                    <button wire:click="setTypeFilter('folder')" class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $typeFilter === 'folder' ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">Folders</button>
                    <button wire:click="setTypeFilter('global')" class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $typeFilter === 'global' ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">Global</button>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="uppercase tracking-wide font-semibold">Sort by</span>
                    <select wire:model.live="sortBy" class="text-sm text-slate-700 border border-slate-200 rounded-md px-2 py-1 bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none">
                        <option value="updated_at">Last Updated</option>
                        <option value="name">Name</option>
                        <option value="created_at">Created</option>
                        <option value="status">Status</option>
                    </select>
                </div>
            </div>

            {{-- Table header --}}
            <div class="grid grid-cols-[auto_1fr_140px_180px_140px] items-center gap-4 px-5 py-3 border-b border-slate-100 bg-slate-50/50 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                <div class="w-5"><input type="checkbox" class="rounded border-slate-300 text-teal-500 focus:ring-teal-500" disabled /></div>
                <div>Name</div>
                <div>Content Type</div>
                <div>Last Updated</div>
                <div>Status</div>
            </div>

            {{-- Table rows (tree: expandable folders) --}}
            @forelse($this->contentTree as $row)
                @php $content = $row->content; $depth = $row->depth; @endphp
                <div class="grid grid-cols-[auto_1fr_140px_180px_140px] items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-slate-50/50 transition-colors group" wire:key="content-{{ $content->id }}">
                    {{-- Checkbox --}}
                    <div class="w-5"><input type="checkbox" class="rounded border-slate-300 text-teal-500 focus:ring-teal-500" /></div>

                    {{-- Name + slug (indent by depth, expand/collapse for folders) --}}
                    <div class="flex items-center gap-2 min-w-0" style="padding-left: {{ $depth * 20 }}px;">
                        @if($content->isFolder())
                            <button type="button" wire:click="toggleFolder({{ $content->id }})" class="w-6 h-6 flex items-center justify-center rounded text-slate-400 hover:text-slate-600 hover:bg-slate-100 shrink-0 transition-colors" aria-label="{{ $this->isFolderExpanded($content->id) ? 'Collapse' : 'Expand' }}">
                                @if($this->isFolderExpanded($content->id))
                                    <i class="ph ph-caret-down text-sm"></i>
                                @else
                                    <i class="ph ph-caret-right text-sm"></i>
                                @endif
                            </button>
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                                <i class="ph-fill ph-folder text-sm"></i>
                            </div>
                        @else
                            <div class="w-6 shrink-0" aria-hidden="true"></div>
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                                <i class="ph ph-file-text text-sm"></i>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            @if($content->isPage())
                                <a href="{{ route('admin.content.editor', $content) }}" wire:navigate class="text-sm font-semibold text-slate-800 hover:text-teal-600 transition-colors truncate block">{{ $content->name }}</a>
                            @elseif($content->isFolder())
                                <span class="text-sm font-semibold text-slate-800 truncate block">{{ $content->name }}</span>
                            @else
                                <a href="{{ route('admin.content.editor', $content) }}" wire:navigate class="text-sm font-semibold text-slate-800 hover:text-teal-600 transition-colors truncate block">{{ $content->name }}</a>
                            @endif
                            <span class="text-xs text-slate-400 truncate block">/{{ $content->slug }}</span>
                        </div>
                    </div>

                    {{-- Content type badge --}}
                    <div>
                        @if($content->isFolder())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-600">Folder</span>
                        @elseif($content->type === 'global')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-600">Global</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-50 text-teal-600">Page</span>
                        @endif
                    </div>

                    {{-- Last updated --}}
                    <div>
                        <div class="text-sm text-slate-700">{{ $content->updated_at->diffForHumans() }}</div>
                        <div class="text-xs text-slate-400">by {{ $content->updater?->name ?? $content->creator?->name ?? 'System' }}</div>
                    </div>

                    {{-- Status --}}
                    <div class="flex items-center gap-2">
                        @if($content->status === 'published')
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <span class="text-sm text-slate-700">Published</span>
                        @elseif($content->status === 'draft')
                            <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                            <span class="text-sm text-slate-500">Draft</span>
                        @else
                            <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                            <span class="text-sm text-slate-700">{{ ucfirst($content->status) }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-20 px-4">
                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                        <i class="ph ph-folder-open text-2xl text-slate-400"></i>
                    </div>
                    <p class="text-sm font-medium text-slate-700">No content found</p>
                    <p class="text-xs text-slate-400 mt-1">Get started by creating a folder or page.</p>
                    @can('create content')
                    <div class="mt-5 flex gap-3">
                        <a href="{{ route('admin.content.create', ['type' => 'folder', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-md hover:bg-slate-50 transition-colors shadow-sm">New Folder</a>
                        <a href="{{ route('admin.content.create', ['type' => 'page', 'parent_id' => $selectedFolderId]) }}" wire:navigate class="px-4 py-2 text-sm font-medium text-white bg-teal-500 hover:bg-teal-600 rounded-md transition-colors shadow-sm">New Page</a>
                    </div>
                    @endcan
                </div>
            @endforelse

        </div>

        {{-- Tip --}}
        <div class="flex items-center justify-center gap-2 mt-6 text-xs text-slate-400">
            <i class="ph ph-info"></i>
            <span>Tip: You can drag and drop folders to reorder your content structure.</span>
        </div>

        </div>{{-- /max-w centered --}}
    </main>

    {{-- Right aside: Space Activity --}}
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Space Activity">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0">
            <h2 class="text-sm font-bold text-slate-800">Space Activity</h2>
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
            @forelse($this->recentActivity as $activity)
                <div class="px-5 py-4 flex gap-3">
                    @if($activity->user)
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center text-white text-[10px] font-bold shrink-0 mt-0.5">
                            {{ strtoupper(substr($activity->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $activity->user->name)[1] ?? '', 0, 1)) }}
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-teal-600 shrink-0 mt-0.5">
                            API
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-700 leading-snug">
                            <span class="font-semibold">{{ $activity->user?->name ?? 'System' }}</span>
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
                                    <a href="{{ $subjectRoute }}" wire:navigate class="text-teal-600 font-medium hover:underline">{{ $subjectName }}</a>.
                                @else
                                    <span class="text-teal-600 font-medium">{{ $subjectName }}</span>.
                                @endif
                            @endif
                        </p>
                        <span class="text-xs text-slate-400 mt-1 block">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center">
                    <i class="ph ph-clock-counter-clockwise text-2xl text-slate-300"></i>
                    <p class="text-xs text-slate-400 mt-2">No recent activity</p>
                </div>
            @endforelse
        </div>
    </aside>
    </div>
</div>
