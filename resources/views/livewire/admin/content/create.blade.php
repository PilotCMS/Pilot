<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Create {{ ucfirst($type) }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">Create a new content entry</p>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">

    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            <div class="max-w-2xl">
                <div class="mb-8">
                    <a href="{{ route('admin.content.index') }}" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 mb-4 transition-colors" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Content
                    </a>
                    <flux:heading>Create {{ ucfirst($type) }}</flux:heading>
                </div>

                <flux:card>
                    <form wire:submit="save" class="space-y-6">
                        @if($parent)
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800/50">
                                <flux:text class="text-sm">
                                    Creating in folder: <strong>{{ $parent->name }}</strong>
                                </flux:text>
                            </div>
                        @endif

                        <flux:field>
                            <flux:label>Type</flux:label>
                            <flux:radio.group wire:model="type">
                                <flux:radio value="page">Page</flux:radio>
                                <flux:radio value="folder">Folder</flux:radio>
                            </flux:radio.group>
                            <flux:error name="type" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Space</flux:label>
                            <flux:select wire:model="spaceId">
                                <option value="">Select a space...</option>
                                @foreach($spaces as $space)
                                    <option value="{{ $space->id }}">{{ $space->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="spaceId" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Name</flux:label>
                            <flux:input wire:model="name" placeholder="My Page" />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Slug</flux:label>
                            <flux:input wire:model="slug" placeholder="my-page" />
                            <flux:error name="slug" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select wire:model="status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </flux:select>
                            <flux:error name="status" />
                        </flux:field>

                        <div class="flex items-center justify-end gap-3">
                            <flux:button href="{{ route('admin.content.index') }}" wire:navigate variant="ghost">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                Create {{ ucfirst($type) }}
                            </flux:button>
                        </div>
                    </form>
                </flux:card>
            </div>
        </div>
    </main>

    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0">
            <h2 class="text-sm font-bold text-slate-800">Details</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500 flex items-center justify-center">
            <p>Configure your new content entry.</p>
        </div>
    </aside>
    </div>
</div>
