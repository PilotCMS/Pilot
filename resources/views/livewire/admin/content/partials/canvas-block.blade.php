@php
    $isSelected = $selectedBlockId === $block['id'];
    $blockType = $blockTypes[$block['type']] ?? null;
    $canContainBlocks = (bool) ($blockType?->schema['can_contain_blocks'] ?? false);
    $children = collect($block['children'] ?? [])->values();
    $columnCount = (int) ($block['data']['columns'] ?? 2);
    $columnCount = max(1, min(4, $columnCount));
    $columnClasses = [
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
    ][$columnCount];
    $hasColumnSlots = in_array($block['type'], ['columns', 'grid'], true);
    $isNested = $depth > 0;
    $childrenForColumn = function (int $columnIndex) use ($children, $columnCount) {
        return $children->filter(function ($child, $index) use ($columnIndex, $columnCount) {
            $childColumn = array_key_exists('_column', $child['data'] ?? [])
                ? (int) $child['data']['_column']
                : $index % $columnCount;

            return $childColumn === $columnIndex;
        });
    };
@endphp

<div
    wire:key="block-{{ $block['id'] }}"
    class="group/block relative {{ $isNested ? 'mb-3 rounded-lg border bg-white p-2 shadow-sm transition-colors' : 'mb-8 rounded-lg transition-all duration-200' }} {{ $isSelected ? ($isNested ? 'border-teal-300 ring-2 ring-teal-100' : 'editor-highlight') : ($isNested ? 'border-slate-200 hover:border-teal-200' : 'hover-highlight') }}"
    @if($isSelected) data-label="{{ $blockType->name ?? $block['type'] }}" @endif
>
    <div class="absolute right-2 top-2 z-20 flex overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm transition-opacity {{ $isSelected ? 'opacity-100' : 'opacity-0 group-hover/block:opacity-100' }}">
        <button type="button" wire:click.stop="moveBlockUp({{ $block['id'] }})" class="flex h-7 w-7 items-center justify-center border-r border-slate-200 text-slate-400 transition-colors hover:bg-slate-50 hover:text-teal-600" title="Move block up" aria-label="Move block up">
            <i class="ph ph-arrow-up text-sm"></i>
        </button>
        <button type="button" wire:click.stop="moveBlockDown({{ $block['id'] }})" class="flex h-7 w-7 items-center justify-center text-slate-400 transition-colors hover:bg-slate-50 hover:text-teal-600" title="Move block down" aria-label="Move block down">
            <i class="ph ph-arrow-down text-sm"></i>
        </button>
    </div>

    <div
        wire:click="$set('selectedBlockId', {{ $block['id'] }})"
        class="relative z-10 cursor-pointer rounded-md transition-colors {{ $isNested ? 'px-2 py-2' : '-mx-1 px-1 py-2' }} {{ $isSelected ? 'hover:bg-teal-50/30' : 'hover:bg-slate-50/80' }}"
    >
        @if($isNested)
            <div class="mb-2 flex items-center gap-2 pr-16 text-xs font-medium text-slate-500">
                <span class="flex h-5 w-5 items-center justify-center rounded bg-slate-100 text-[10px] font-semibold text-slate-500">
                    {{ $blockType ? strtoupper(mb_substr($blockType->name, 0, 1)) : 'B' }}
                </span>
                <span class="truncate">{{ $blockType->name ?? $block['type'] }}</span>
            </div>
        @endif

        <x-fallback :block="$block" :data="$block['data']" :children="$block['children'] ?? []" />
    </div>

    @if($canContainBlocks && $hasColumnSlots)
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nested content</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-400 ring-1 ring-slate-200">{{ $columnCount }} columns</span>
            </div>

            <div class="grid gap-3 p-3 {{ $columnClasses }}">
                @foreach(range(0, $columnCount - 1) as $columnIndex)
                    @php
                        $columnChildren = $childrenForColumn($columnIndex);
                    @endphp

                    <div class="min-h-32 rounded-lg border border-dashed border-slate-200 bg-slate-50/70 p-2 transition-colors hover:border-teal-300 hover:bg-teal-50/30">
                        <div class="mb-2 flex items-center justify-between px-1">
                            <span class="text-xs font-medium text-slate-500">Column {{ $columnIndex + 1 }}</span>
                            <button type="button" wire:click="addNestedBlock({{ $block['id'] }}, {{ $columnIndex }})" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-white hover:text-teal-600 hover:shadow-sm" title="Add block to column {{ $columnIndex + 1 }}" aria-label="Add block to column {{ $columnIndex + 1 }}">
                                <i class="ph ph-plus"></i>
                            </button>
                        </div>

                        @if($columnChildren->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($columnChildren as $child)
                                    @include('livewire.admin.content.partials.canvas-block', [
                                        'block' => $child,
                                        'blockTypes' => $blockTypes,
                                        'selectedBlockId' => $selectedBlockId,
                                        'depth' => $depth + 1,
                                    ])
                                @endforeach
                            </div>
                        @else
                            <button type="button" wire:click="addNestedBlock({{ $block['id'] }}, {{ $columnIndex }})" class="flex min-h-24 w-full items-center justify-center gap-2 rounded-md border border-dashed border-slate-300 bg-white/70 px-3 py-4 text-sm font-medium text-slate-500 transition-colors hover:border-teal-400 hover:text-teal-700">
                                <i class="ph ph-plus-circle text-base"></i>
                                Add block
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($canContainBlocks)
        <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Child blocks</span>
                <button type="button" wire:click="addNestedBlock({{ $block['id'] }})" class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-slate-500 transition-colors hover:bg-white hover:text-teal-700 hover:shadow-sm">
                    <i class="ph ph-plus"></i>
                    Add
                </button>
            </div>

            @if($children->isNotEmpty())
                <div class="space-y-3 p-3">
                    @foreach($children as $child)
                        @include('livewire.admin.content.partials.canvas-block', [
                            'block' => $child,
                            'blockTypes' => $blockTypes,
                            'selectedBlockId' => $selectedBlockId,
                            'depth' => $depth + 1,
                        ])
                    @endforeach
                </div>
            @else
                <div class="p-3">
                    <button type="button" wire:click="addNestedBlock({{ $block['id'] }})" class="flex min-h-24 w-full items-center justify-center gap-2 rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-sm font-medium text-slate-500 transition-colors hover:border-teal-400 hover:text-teal-700">
                        <i class="ph ph-plus-circle text-base"></i>
                        Add child block
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
