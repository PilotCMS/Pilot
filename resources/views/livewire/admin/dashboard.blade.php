<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    {{-- Fixed header: full width between left nav and viewport edge --}}
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Content</h1>
            <p class="text-xs text-slate-500 mt-0.5">Overview</p>
        </div>
        <div class="flex items-center gap-4 text-sm font-semibold">
            <a href="{{ route('admin.content.index') }}" class="text-teal-600 hover:text-teal-700" wire:navigate>All content</a>
            <a href="{{ route('admin.assets.index') }}" class="text-slate-600 hover:text-slate-900" wire:navigate>Assets</a>
            <a href="{{ route('admin.blocks.index') }}" class="text-slate-600 hover:text-slate-900" wire:navigate>Block types</a>
            @can('create content')
            <a href="{{ route('admin.content.create', ['type' => 'page']) }}" class="rounded-lg bg-teal-500 px-3 py-2 text-white hover:bg-teal-600 transition-colors" wire:navigate>
                <i class="ph ph-plus -ml-0.5"></i> New page
            </a>
            @endcan
        </div>
    </header>

    <div class="flex flex-1 min-h-0">

    {{-- Main content --}}
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full px-6 sm:px-8 py-8 relative isolate">
    {{-- Stats cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                <i class="ph-fill ph-files text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($pagesCount) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">Pages</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center text-teal-500 shrink-0">
                <i class="ph-fill ph-image text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($assetsCount) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">Assets</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500 shrink-0">
                <i class="ph-fill ph-users text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($usersCount) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">Users</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-500 shrink-0">
                <i class="ph-fill ph-pencil-simple text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900">{{ number_format($draftsCount) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">Drafts</div>
            </div>
        </div>
    </div>

    {{-- Decorative gradient blob --}}
    <div aria-hidden="true" class="absolute top-full left-0 -z-10 mt-96 origin-top-left translate-y-40 -rotate-90 transform-gpu opacity-20 blur-3xl sm:left-1/2 sm:-mt-10 sm:-ml-96 sm:translate-y-0 sm:rotate-0 sm:opacity-30">
        <div style="clip-path: polygon(100% 38.5%, 82.6% 100%, 60.2% 37.7%, 52.4% 32.1%, 47.5% 41.8%, 45.2% 65.6%, 27.5% 23.4%, 0.1% 35.3%, 17.9% 0%, 27.7% 23.4%, 76.2% 2.5%, 74.2% 56%, 100% 38.5%)" class="aspect-[1154/678] w-[288.5px] bg-gradient-to-br from-teal-400 to-blue-500"></div>
    </div>

    <div class="space-y-12 pt-4 sm:space-y-16 sm:pt-0">
        {{-- Recent activity table --}}
        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-4">Recent activity</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
                <div class="px-6 py-6">
                    <div class="w-full">
                        @php
                            $groupedActivities = $recentActivities->groupBy(fn ($a) => $a->created_at->format('Y-m-d'));
                            $today = now()->format('Y-m-d');
                            $yesterday = now()->subDay()->format('Y-m-d');
                        @endphp
                        @if($recentActivities->isNotEmpty())
                        <table class="w-full text-left">
                            <thead class="sr-only">
                                <tr>
                                    <th>Action</th>
                                    <th class="hidden sm:table-cell">User</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupedActivities as $date => $activities)
                                <tr class="text-sm text-zinc-900 dark:text-white">
                                    <th scope="colgroup" colspan="3" class="relative isolate py-3 font-semibold">
                                        <time datetime="{{ $date }}">
                                            @if($date === $today)
                                                Today
                                            @elseif($date === $yesterday)
                                                Yesterday
                                            @else
                                                {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
                                            @endif
                                        </time>
                                        <div class="absolute inset-y-0 right-full -z-10 w-screen border-b border-slate-200 bg-white/40"></div>
                                        <div class="absolute inset-y-0 left-0 -z-10 w-screen border-b border-slate-200 bg-white/40"></div>
                                    </th>
                                </tr>
                                @foreach($activities as $activity)
                                <tr>
                                    <td class="relative py-5 pr-6">
                                        <div class="flex gap-x-6">
                                            <i class="ph ph-lightning hidden size-5 flex-none text-slate-400 sm:block"></i>
                                            <div class="flex-auto">
                                                <div class="flex items-start gap-x-3">
                                                    <div class="text-sm font-medium text-slate-900">
                                                        {{ ucfirst($activity->action) }} {{ class_basename($activity->subject_type) }}
                                                    </div>
                                                    @if($activity->subject)
                                                    <span class="rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-teal-600/20 ring-inset">
                                                        {{ $activity->subject->name ?? 'Unknown' }}
                                                    </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500">{{ $activity->created_at->format('g:i A') }}</div>
                                            </div>
                                        </div>
                                        <div class="absolute right-full bottom-0 h-px w-screen bg-slate-100"></div>
                                        <div class="absolute bottom-0 left-0 h-px w-screen bg-slate-100"></div>
                                    </td>
                                    <td class="hidden py-5 pr-6 sm:table-cell">
                                        <div class="text-sm text-slate-900">{{ $activity->user?->name ?? 'Unknown' }}</div>
                                    </td>
                                    <td class="py-5 text-right">
                                        <div class="flex justify-end">
                                            <span class="text-sm text-slate-500">{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                <i class="ph ph-lightning size-7 text-slate-400"></i>
                            </div>
                            <h3 class="mt-4 text-sm font-semibold text-slate-900">No recent activity</h3>
                            <p class="mt-2 text-sm text-slate-500">Activity from you and your team will appear here.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent pages grid --}}
        <div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <h2 class="text-base font-semibold text-slate-900">Continue editing</h2>
                <a href="{{ route('admin.content.index') }}" class="text-sm font-semibold text-teal-600 hover:text-teal-700" wire:navigate>
                    View all<span class="sr-only">, content</span>
                </a>
            </div>
            @if($recentPages->isNotEmpty())
                <ul role="list" class="grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-3">
                    @foreach($recentPages as $page)
                    <li class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft transition-shadow hover:shadow-float">
                        <a href="{{ route('admin.content.editor', $page) }}" class="block" wire:navigate>
                            <div class="flex items-center gap-x-4 border-b border-slate-100 bg-slate-50/50 p-6">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 shadow-sm">
                                    <i class="ph ph-file-text size-6"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-slate-900 truncate">{{ $page->name }}</div>
                                    <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $page->status === 'published' ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($page->status) }}</span>
                                </div>
                                <i class="ph ph-caret-right size-5 shrink-0 text-slate-400"></i>
                            </div>
                            <dl class="-my-3 divide-y divide-slate-100 px-6 py-4 text-sm">
                                <div class="flex justify-between gap-x-4 py-3">
                                    <dt class="text-slate-500">Last updated</dt>
                                    <dd class="text-slate-700">{{ $page->updated_at->format('M j, Y') }}</dd>
                                </div>
                                <div class="flex justify-between gap-x-4 py-3">
                                    <dt class="text-slate-500">Slug</dt>
                                    <dd class="font-mono text-xs text-slate-700 truncate max-w-[120px]">/{{ $page->slug }}</dd>
                                </div>
                            </dl>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="mt-6 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                        <i class="ph ph-file-text size-7 text-slate-400"></i>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold text-slate-900">No recent pages</h3>
                    <p class="mt-2 text-sm text-slate-500">Pages you've edited recently will appear here.</p>
                    @can('create content')
                    <a href="{{ route('admin.content.create', ['type' => 'page']) }}" wire:navigate class="mt-6 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-teal-500 hover:bg-teal-600 rounded-lg transition-colors">
                        <i class="ph ph-plus"></i>
                        Create a page
                    </a>
                    @endcan
                </div>
            @endif
        </div>
        </div>{{-- /max-w centered --}}
    </main>

    {{-- Right aside: fixed to viewport right, below top bar --}}
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0">
            <h2 class="text-sm font-bold text-slate-800">Details</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500 flex items-center justify-center">
            <p>Select an item or use this space for context.</p>
        </div>
    </aside>
    </div>
</div>
