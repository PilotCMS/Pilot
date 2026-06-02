@php
    $src = $block['data']['image'] ?? null;
    $alt = $block['data']['alt'] ?? '';
    $focalX = $block['data']['image_focal_x'] ?? 50;
    $focalY = $block['data']['image_focal_y'] ?? 50;
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    @if($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="w-full rounded-xl object-cover aspect-video" style="object-position: {{ $focalX }}% {{ $focalY }}%;" />
    @else
        <div class="rounded-xl border-2 border-dashed border-slate-200 p-10 text-center text-sm text-slate-400">No image configured</div>
    @endif
</section>
