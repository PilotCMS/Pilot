<div class="flex h-full min-w-0 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header title="Content Types" subtitle="Define page models, fields, blocks, and URL behavior." top="0px" as="header" scroll-target="#content-types-scroll" aria-label="Page header">
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <button type="button" wire:click="create" class="cms-btn cms-btn-primary">
                <x-jaunt.icon name="plus" size="sm" />
                New content type
            </button>
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="grid flex-1 min-h-0 grid-cols-[minmax(0,1fr)_var(--admin-rail-width)]">
        <main id="content-types-scroll" class="min-w-0 overflow-y-auto p-6">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($contentTypes as $contentType)
                    <button type="button" wire:click="edit({{ $contentType->id }})" class="rounded-lg border border-slate-200 bg-white p-5 text-left shadow-sm transition-colors hover:border-blue-300 hover:bg-blue-50/30">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-slate-900">{{ $contentType->name }}</h2>
                                <p class="mt-1 text-xs font-mono text-slate-500">{{ $contentType->key }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $contentType->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $contentType->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="mt-3 line-clamp-2 text-sm text-slate-500">{{ $contentType->description ?: 'No description yet.' }}</p>
                        <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                            <span>{{ count($contentType->schema['fields'] ?? []) }} fields</span>
                            <span>&middot;</span>
                            <span>{{ count($contentType->allowed_blocks ?? []) }} blocks</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-lg border-2 border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                        No content types yet. Create one to define structured pages.
                    </div>
                @endforelse
            </div>
        </main>

        <aside class="flex min-h-0 flex-col border-l border-slate-200 bg-white shadow-xl">
            <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
                <div class="h-14 shrink-0 border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-800">{{ $editingId ? 'Edit Content Type' : 'New Content Type' }}</h2>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto p-5">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model.live="name" placeholder="Blog Post" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Key</flux:label>
                        <flux:input wire:model="key" placeholder="blog-post" />
                        <flux:error name="key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="3" />
                    </flux:field>

                    <flux:field>
                        <flux:label>URL Pattern</flux:label>
                        <flux:input wire:model="settings.url_pattern" placeholder="/blog/{slug}" />
                        <flux:description>Use <code>{slug}</code> where the content slug should appear.</flux:description>
                        <flux:error name="settings.url_pattern" />
                    </flux:field>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-600">Fields</span>
                            <div class="flex gap-1">
                                @foreach(['text', 'textarea', 'richtext', 'image', 'reference', 'boolean'] as $fieldType)
                                    <button type="button" wire:click="addFieldOfType('{{ $fieldType }}')" class="rounded border border-slate-200 px-2 py-1 text-xs text-slate-600 hover:bg-slate-50">{{ $fieldType }}</button>
                                @endforeach
                            </div>
                        </div>

                        @foreach($schema['fields'] ?? [] as $index => $field)
                            <div wire:click="selectField({{ $index }})" class="rounded-lg border p-3 {{ $selectedFieldIndex === $index ? 'border-blue-500 bg-blue-50/50' : 'border-slate-200' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-slate-800">{{ $field['label'] ?: 'Untitled field' }}</div>
                                        <div class="text-xs text-slate-500">{{ $field['type'] }} · {{ $field['key'] ?: 'key' }}</div>
                                    </div>
                                    <button type="button" wire:click.stop="removeField({{ $index }})" class="text-slate-400 hover:text-red-500">
                                        <x-jaunt.icon name="trash-2" size="sm" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($selectedFieldIndex !== null && isset($schema['fields'][$selectedFieldIndex]))
                        <div class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <flux:field>
                                <flux:label>Field Label</flux:label>
                                <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.label" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Field Key</flux:label>
                                <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.key" />
                            </flux:field>
                            <div class="grid grid-cols-2 gap-3">
                                <flux:field>
                                    <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.required" label="Required" />
                                </flux:field>
                                <flux:field>
                                    <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.translatable" label="Translatable" />
                                </flux:field>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-600">Allowed Blocks</span>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($blockTypes as $blockType)
                                <label class="flex items-center gap-2 rounded border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                    <input type="checkbox" wire:model="allowedBlocks" value="{{ $blockType->key }}" class="rounded border-slate-300 text-blue-500 focus:ring-blue-500">
                                    <span>{{ $blockType->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <flux:field>
                        <flux:checkbox wire:model="isActive" label="Active" />
                    </flux:field>
                </div>

                <div class="flex shrink-0 justify-end gap-2 border-t border-slate-200 bg-slate-50 p-4">
                    <button type="button" wire:click="resetForm" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Cancel</button>
                    <flux:button type="submit" variant="primary">Save Content Type</flux:button>
                </div>
            </form>
        </aside>
    </div>
</div>
