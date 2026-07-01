<div class="cms-shell flex h-full w-full min-w-0 flex-col">
    <header class="cms-topbar" aria-label="Dashboard header">
        <div class="min-w-0">
            <h1 class="cms-title">Good afternoon, {{ str(auth()->user()->name)->before(' ') }}</h1>
            <p class="cms-subtitle">Here's what's happening across {{ $space?->name ?? 'Pilot CMS' }}.</p>
        </div>

        <div class="cms-actions">
            <a href="{{ route('admin.assets.index') }}" wire:navigate class="cms-btn cms-btn-secondary">
                <i class="ph ph-upload" aria-hidden="true"></i>
                Import
            </a>
            @can('create content')
                <a href="{{ route('admin.content.create', ['type' => 'page']) }}" wire:navigate class="cms-btn cms-btn-primary">
                    <i class="ph ph-plus" aria-hidden="true"></i>
                    New page
                </a>
            @endcan
        </div>
    </header>

    <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px]">
        <main class="min-w-0 overflow-y-auto">
            <div class="flex min-h-full flex-col gap-7 p-[var(--pad-view)]">
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($pagesCount) }}</div>
                                <div class="cms-subtitle">Pages</div>
                            </div>
                            <span class="cms-tile cms-tile-info"><i class="ph-fill ph-files" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($assetsCount) }}</div>
                                <div class="cms-subtitle">Assets</div>
                            </div>
                            <span class="cms-tile cms-tile-accent"><i class="ph-fill ph-image" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($usersCount) }}</div>
                                <div class="cms-subtitle">Users</div>
                            </div>
                            <span class="cms-tile bg-info-subtle text-info"><i class="ph-fill ph-users" aria-hidden="true"></i></span>
                        </div>
                    </div>
                    <div class="cms-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-2xl font-semibold tabular-nums text-primary">{{ number_format($draftsCount) }}</div>
                                <div class="cms-subtitle">Drafts</div>
                            </div>
                            <span class="cms-tile bg-warning-subtle text-warning"><i class="ph-fill ph-pencil-simple" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </div>

                <section class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="cms-panel">
                        <div class="flex items-center justify-between gap-3 border-b border-subtle px-5 py-4">
                            <h2 class="text-sm font-semibold text-primary">Recent activity</h2>
                            <a href="{{ route('admin.content.index') }}" wire:navigate class="text-sm font-medium text-accent-text hover:underline">View all</a>
                        </div>

                        <div class="p-5">
                            @php
                                $groupedActivities = $recentActivities->groupBy(fn ($a) => $a->created_at->format('Y-m-d'));
                                $today = now()->format('Y-m-d');
                                $yesterday = now()->subDay()->format('Y-m-d');
                            @endphp

                            @if($recentActivities->isNotEmpty())
                                <div class="flex flex-col">
                                    @foreach($groupedActivities as $date => $activities)
                                        <div class="border-b border-subtle pb-2 pt-3 first:pt-0 text-2xs font-semibold uppercase tracking-[0.06em] text-tertiary">
                                            @if($date === $today)
                                                Today
                                            @elseif($date === $yesterday)
                                                Yesterday
                                            @else
                                                {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                                            @endif
                                        </div>

                                        @foreach($activities as $activity)
                                            <div class="flex items-center gap-3 border-b border-subtle py-3 last:border-b-0">
                                                <span class="cms-avatar">
                                                    {{ $activity->user ? strtoupper(substr($activity->user->name, 0, 1)) : 'P' }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="text-sm text-secondary">
                                                        <strong class="font-semibold text-primary">{{ $activity->user?->name ?? 'System' }}</strong>
                                                        {{ $activity->action }}
                                                        @if($activity->subject)
                                                            <span class="font-medium text-accent-text">{{ $activity->subject->name ?? 'Unknown' }}</span>
                                                        @else
                                                            {{ class_basename($activity->subject_type) }}
                                                        @endif
                                                    </div>
                                                    <time class="mt-1 block text-2xs text-tertiary">{{ $activity->created_at->diffForHumans() }}</time>
                                                </div>
                                                <span class="cms-badge">{{ $activity->created_at->format('g:i A') }}</span>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            @else
                                <div class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="cms-tile !h-14 !w-14 !rounded-lg"><i class="ph ph-lightning text-2xl" aria-hidden="true"></i></div>
                                    <h3 class="mt-4 text-sm font-semibold text-primary">No recent activity</h3>
                                    <p class="cms-subtitle">Activity from you and your team will appear here.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <div class="cms-panel p-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-primary">
                                <i class="ph ph-sparkle text-ai" aria-hidden="true"></i>
                                Jaunt insight
                            </div>
                            <p class="mt-2 text-sm text-secondary">Draft volume is {{ number_format($draftsCount) }}. Review recently edited pages before the next publishing window.</p>
                            <a href="{{ route('admin.content.index', ['type' => 'draft']) }}" wire:navigate class="cms-btn cms-btn-secondary mt-4 !h-control-sm">
                                <i class="ph ph-list-checks" aria-hidden="true"></i>
                                Review drafts
                            </a>
                        </div>

                        <div class="cms-panel p-4">
                            <h2 class="text-sm font-semibold text-primary">Publishing</h2>
                            <div class="mt-4 flex flex-col gap-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-tertiary">Published</span>
                                    <span class="font-mono text-primary">{{ number_format($pagesCount - $draftsCount) }} / {{ number_format($pagesCount) }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-sunken">
                                    <div class="h-full rounded-full bg-accent" style="width: {{ $pagesCount > 0 ? max(4, (($pagesCount - $draftsCount) / $pagesCount) * 100) : 0 }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-tertiary">Drafts awaiting review</span>
                                    <span class="cms-badge cms-badge-warning">{{ number_format($draftsCount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-primary">Continue editing</h2>
                        <a href="{{ route('admin.content.index') }}" wire:navigate class="text-sm font-medium text-accent-text hover:underline">View all content</a>
                    </div>

                    @if($recentPages->isNotEmpty())
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            @foreach($recentPages as $page)
                                <a href="{{ route('admin.content.editor', $page) }}" wire:navigate class="cms-panel block transition-shadow hover:shadow-md">
                                    <div class="flex items-center gap-3 border-b border-subtle bg-app p-4">
                                        <span class="cms-tile cms-tile-accent"><i class="ph ph-file-text" aria-hidden="true"></i></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-sm font-medium text-primary">{{ $page->name }}</div>
                                            <span class="mt-1 {{ $page->status === 'published' ? 'cms-badge cms-badge-success' : 'cms-badge' }}">{{ ucfirst($page->status) }}</span>
                                        </div>
                                        <i class="ph ph-caret-right shrink-0 text-tertiary" aria-hidden="true"></i>
                                    </div>
                                    <dl class="p-4 text-sm">
                                        <div class="flex justify-between gap-4 border-b border-subtle py-2">
                                            <dt class="text-tertiary">Updated</dt>
                                            <dd class="text-primary">{{ $page->updated_at->format('M j, Y') }}</dd>
                                        </div>
                                        <div class="flex justify-between gap-4 py-2">
                                            <dt class="text-tertiary">Slug</dt>
                                            <dd class="max-w-36 truncate font-mono text-2xs text-primary">/{{ $page->slug }}</dd>
                                        </div>
                                    </dl>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="cms-panel flex flex-col items-center justify-center py-16 text-center">
                            <div class="cms-tile !h-14 !w-14 !rounded-lg"><i class="ph ph-file-text text-2xl" aria-hidden="true"></i></div>
                            <h3 class="mt-4 text-sm font-semibold text-primary">No recent pages</h3>
                            <p class="cms-subtitle">Pages you've edited recently will appear here.</p>
                        </div>
                    @endif
                </section>
            </div>
        </main>

        <aside class="cms-rail hidden xl:flex" aria-label="Details">
            <div class="cms-rail-head">
                <i class="ph ph-info text-tertiary" aria-hidden="true"></i>
                <h2 class="cms-rail-title">Details</h2>
            </div>
            <div class="flex flex-1 items-center justify-center p-5 text-center text-sm text-tertiary">
                Select an item or use this space for context.
            </div>
        </aside>
    </div>
</div>
