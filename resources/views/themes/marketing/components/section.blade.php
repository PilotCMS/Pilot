@php
    $background = $block['data']['background_color'] ?? '#f8fafc';
@endphp

<section class="rounded-3xl border border-slate-200 p-8 shadow-sm" style="background-color: {{ $background }};">
    @if(!empty($block['children']))
        <div class="space-y-6">
            @foreach($block['children'] as $child)
                @include('themes.marketing.components._render-block', ['block' => $child])
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-500">Section block (no nested children yet).</p>
    @endif
</section>
