@php
    $columnCount = (int) ($block['data']['columns'] ?? 2);
    $columnCount = max(1, min(4, $columnCount));
    $columnClasses = [
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
    ][$columnCount];
    $children = collect($block['children'] ?? [])->values();
    $childrenForColumn = function (int $columnIndex) use ($children, $columnCount) {
        return $children->filter(function ($child, $index) use ($columnIndex, $columnCount) {
            $childColumn = array_key_exists('_column', $child['data'] ?? [])
                ? (int) $child['data']['_column']
                : $index % $columnCount;

            return $childColumn === $columnIndex;
        });
    };
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @if($children->isNotEmpty())
        <div class="grid gap-6 {{ $columnClasses }}">
            @foreach(range(0, $columnCount - 1) as $columnIndex)
                <div class="space-y-6">
                    @foreach($childrenForColumn($columnIndex) as $child)
                        @include('themes.default.components._render-block', ['block' => $child])
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        <div class="grid gap-6 {{ $columnClasses }}">
            @foreach(range(1, $columnCount) as $column)
                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                    Column {{ $column }}
                </div>
            @endforeach
        </div>
    @endif
</section>
