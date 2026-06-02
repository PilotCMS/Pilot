@php
    $background = $block['data']['background_color'] ?? '#ffffff';
@endphp

<section class="rounded-2xl border border-slate-200 p-8 shadow-sm" style="background-color: {{ $background }};">
    @if(!empty($block['children']))
        <div class="space-y-6">
            @foreach($block['children'] as $child)
                @include('themes.default.components._render-block', ['block' => $child])
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500">Section block (add nested blocks to render content here).</p>
    @endif
</section>
