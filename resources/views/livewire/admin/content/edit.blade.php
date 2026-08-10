<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <x-jaunt.shell.dynamic-header :title="'Edit '.ucfirst($content->type)" subtitle="Update content entry settings." top="0px" as="header" scroll-target="#content-edit-scroll" aria-label="Page header" />

    <div class="flex flex-1 min-h-0">

    <main id="content-edit-scroll" class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            <div class="max-w-2xl">
                <div class="mb-6">
                    <a href="{{ route('admin.content.index') }}" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 mb-4 transition-colors" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Content
                    </a>
                </div>

                <flux:card>
                    <form wire:submit="save" class="space-y-6">
                        <flux:field>
                            <flux:label>Name</flux:label>
                            <flux:input wire:model="name" />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Slug</flux:label>
                            <flux:input wire:model="slug" />
                            <flux:error name="slug" />
                        </flux:field>

                        @if($content->isPage())
                        <flux:field>
                            <flux:label>Parent Folder</flux:label>
                            <flux:select wire:model="parentId">
                                <option value="">None (Root)</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="parentId" />
                        </flux:field>
                        @endif

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
                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">Save Changes</span>
                                <span wire:loading wire:target="save">Saving…</span>
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
            <p>Update core fields for this entry.</p>
        </div>
    </aside>
    </div>
</div>
