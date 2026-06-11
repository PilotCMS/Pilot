<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    {{-- Fixed header --}}
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Edit Block Type</h1>
            <p class="text-xs text-slate-500 mt-0.5">Key: <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">{{ $blockType->key }}</code></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.blocks.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                Cancel
            </a>
            <button type="submit" form="block-type-form" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-600 transition-colors">
                <i class="ph ph-check"></i> Save Changes
            </button>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">

    {{-- Main content --}}
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            @php
                $fieldTypes = [
                    ['type' => 'text', 'label' => 'Text', 'desc' => 'Single line text'],
                    ['type' => 'textarea', 'label' => 'Textarea', 'desc' => 'Multi-line text'],
                    ['type' => 'richtext', 'label' => 'Rich Text', 'desc' => 'Formatted content'],
                    ['type' => 'number', 'label' => 'Number', 'desc' => 'Numeric value'],
                    ['type' => 'boolean', 'label' => 'Boolean', 'desc' => 'True or false'],
                    ['type' => 'image', 'label' => 'Image', 'desc' => 'Asset reference'],
                    ['type' => 'reference', 'label' => 'Reference', 'desc' => 'Content relationship'],
                    ['type' => 'select', 'label' => 'Select', 'desc' => 'Choose from options'],
                    ['type' => 'repeater', 'label' => 'Repeater', 'desc' => 'Repeatable group'],
                ];
            @endphp

            <form wire:submit="save" id="block-type-form" class="space-y-8">
                <a href="{{ route('admin.blocks.index') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-teal-600 mb-6 transition-colors" wire:navigate>
                    <i class="ph ph-arrow-left"></i>
                    Back to Block Types
                </a>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h2 class="text-sm font-bold text-slate-800">Block Type</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Name and options</p>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <flux:field>
                                <flux:label>Name</flux:label>
                                <flux:input wire:model="name" />
                                <flux:error name="name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Icon</flux:label>
                                <flux:input wire:model="icon" />
                                <flux:error name="icon" />
                                <flux:description>Icon name (Heroicons)</flux:description>
                            </flux:field>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:checkbox wire:model="isGlobal" label="Available across all spaces" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Fields</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Add and order fields for this block</p>
                        </div>
                        <button type="button" wire:click="addField" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                            <i class="ph ph-plus text-base"></i>
                            Add Field
                        </button>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            @foreach($fieldTypes as $fieldType)
                                <button
                                    type="button"
                                    wire:click="addFieldOfType('{{ $fieldType['type'] }}')"
                                    class="border border-slate-200 rounded-lg p-3 text-left transition-colors hover:bg-slate-50 hover:border-teal-200"
                                >
                                    <div class="font-medium text-sm text-slate-800">{{ $fieldType['label'] }}</div>
                                    <div class="text-xs text-slate-500 mt-1">{{ $fieldType['desc'] }}</div>
                                </button>
                            @endforeach
                        </div>

                        <div class="space-y-3">
                            @forelse($schema['fields'] ?? [] as $index => $field)
                                <div
                                    wire:click="selectField({{ $index }})"
                                    class="border rounded-lg p-4 cursor-pointer transition-colors {{ $selectedFieldIndex === $index ? 'border-teal-500 bg-teal-50/50 ring-1 ring-teal-500/20' : 'border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-slate-800">{{ $field['label'] ?: 'Untitled field' }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $field['type'] ?? 'text' }} · {{ $field['key'] ?: 'key' }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <flux:button type="button" wire:click.stop="moveFieldUp({{ $index }})" variant="ghost" size="xs">
                                                <flux:icon.chevron-up class="size-4" />
                                            </flux:button>
                                            <flux:button type="button" wire:click.stop="moveFieldDown({{ $index }})" variant="ghost" size="xs">
                                                <flux:icon.chevron-down class="size-4" />
                                            </flux:button>
                                            <flux:button type="button" wire:click.stop="removeField({{ $index }})" variant="ghost" size="xs" class="text-red-600 hover:text-red-700">
                                                <flux:icon.trash class="size-4" />
                                            </flux:button>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                                        @if(!empty($field['required']))
                                            <span class="rounded bg-amber-100 text-amber-700 px-1.5 py-0.5">Required</span>
                                        @endif
                                        @if(!empty($field['translatable']))
                                            <span class="rounded bg-slate-100 text-slate-600 px-1.5 py-0.5">Translatable</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500 border-2 border-dashed border-slate-200 rounded-lg p-8 text-center">
                                    No fields yet. Click a field type above to add one.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('admin.blocks.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Cancel
                    </a>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </form>
        </div>
    </main>

    {{-- Right aside: Field Settings --}}
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Field Settings">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0">
            <h2 class="text-sm font-bold text-slate-800">Field Settings</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-5">
            @if($selectedFieldIndex !== null && isset($schema['fields'][$selectedFieldIndex]))
                <div class="space-y-5">
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model="schema.fields.{{ $selectedFieldIndex }}.type">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="richtext">Rich Text</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                            <option value="image">Image</option>
                            <option value="reference">Reference</option>
                            <option value="select">Select</option>
                            <option value="repeater">Repeater</option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Label</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.label" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Key</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.key" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Placeholder</flux:label>
                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.placeholder" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Help Text</flux:label>
                        <flux:textarea wire:model="schema.fields.{{ $selectedFieldIndex }}.help" rows="2"></flux:textarea>
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Default</flux:label>
                            <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.default" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Rows</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.rows" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Min</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.min" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Max</flux:label>
                            <flux:input type="number" wire:model="schema.fields.{{ $selectedFieldIndex }}.max" />
                        </flux:field>
                    </div>

                    <div class="flex items-center gap-4">
                        <flux:field>
                            <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.required" label="Required" />
                        </flux:field>
                        <flux:field>
                            <flux:checkbox wire:model="schema.fields.{{ $selectedFieldIndex }}.translatable" label="Translatable" />
                        </flux:field>
                    </div>

                    @if(($schema['fields'][$selectedFieldIndex]['type'] ?? '') === 'select')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <flux:label>Options</flux:label>
                                <flux:button type="button" wire:click="addOption({{ $selectedFieldIndex }})" variant="ghost" size="xs">
                                    <flux:icon.plus class="size-4" />
                                    Add Option
                                </flux:button>
                            </div>
                            <div class="space-y-2">
                                @foreach($schema['fields'][$selectedFieldIndex]['options'] ?? [] as $optionIndex => $option)
                                    <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.options.{{ $optionIndex }}.value" placeholder="value" />
                                        <flux:input wire:model="schema.fields.{{ $selectedFieldIndex }}.options.{{ $optionIndex }}.label" placeholder="Label" />
                                        <flux:button type="button" wire:click="removeOption({{ $selectedFieldIndex }}, {{ $optionIndex }})" variant="ghost" size="xs" class="text-red-600">
                                            <flux:icon.trash class="size-4" />
                                        </flux:button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-slate-500">Select a field in the list to configure its settings.</p>
            @endif
        </div>
    </aside>
    </div>
</div>
