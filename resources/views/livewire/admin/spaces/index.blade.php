<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Spaces</h1>
            <p class="text-xs text-slate-500 mt-0.5">Organize content, assets, and datasources by project or site</p>
        </div>
        <a href="{{ route('admin.spaces.create') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 transition-colors">
            <i class="ph ph-plus"></i> New Space
        </a>
    </header>

    <div class="flex flex-1 min-h-0">
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
        <div class="mb-6">
            <flux:heading>Spaces</flux:heading>
            <flux:text class="text-muted-foreground text-sm mt-1">Organize content, assets, and datasources by project or site</flux:text>
        </div>
        <div class="flex flex-wrap items-center gap-3 mb-8 pb-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:button href="{{ route('admin.spaces.create') }}" wire:navigate variant="primary" size="sm">
                <flux:icon.plus class="size-4" />
                New Space
            </flux:button>
        </div>

        <flux:card class="overflow-hidden rounded-2xl">
            <flux:table>
                <flux:table.head>
                    <flux:table.row>
                        <flux:table.header>Name</flux:table.header>
                        <flux:table.header>Slug</flux:table.header>
                        <flux:table.header>Contents</flux:table.header>
                        <flux:table.header>Assets</flux:table.header>
                        <flux:table.header>Datasources</flux:table.header>
                        <flux:table.header>Created</flux:table.header>
                        <flux:table.header class="text-right">Actions</flux:table.header>
                    </flux:table.row>
                </flux:table.head>
                <flux:table.body>
                    @forelse($spaces as $space)
                        <flux:table.row class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:table.cell>
                                <div class="font-medium">{{ $space->name }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <code class="text-sm">{{ $space->slug }}</code>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->contents_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->assets_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $space->datasources_count }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="text-sm text-muted-foreground">
                                    {{ $space->created_at->format('M d, Y') }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('admin.spaces.edit', $space) }}" wire:navigate size="sm" variant="ghost">
                                        <flux:icon.pencil class="size-4" />
                                        Edit
                                    </flux:button>
                                    <flux:button
                                        wire:click="deleteSpace({{ $space->id }})"
                                        wire:confirm="Are you sure you want to delete this space? This will delete all associated content, assets, and datasources."
                                        size="sm"
                                        variant="ghost"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <flux:icon.trash class="size-4" />
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-16">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                                        <flux:icon.squares-plus class="size-7 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <flux:heading size="sm" class="mt-4">No spaces yet</flux:heading>
                                    <flux:text class="mt-2 text-sm text-muted-foreground max-w-sm">Spaces organize your content, assets, and datasources. Create one to get started.</flux:text>
                                    <flux:button href="{{ route('admin.spaces.create') }}" variant="primary" class="mt-6" wire:navigate>
                                        <flux:icon.plus class="size-4" />
                                        Create space
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.body>
            </flux:table>
        </flux:card>
        </div>
    </main>
    </div>
</div>
