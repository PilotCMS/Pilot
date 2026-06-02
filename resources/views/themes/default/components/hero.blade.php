@php
    $title = $block['data']['title'] ?? 'Hero title';
    $subtitle = $block['data']['subtitle'] ?? null;
    $ctaLabel = $block['data']['cta_label'] ?? null;
    $ctaUrl = $block['data']['cta_url'] ?? '#';
@endphp

<section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-teal-50 to-cyan-100 p-10 shadow-sm">
    <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-cyan-200/50 blur-2xl"></div>
    <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full bg-teal-200/60 blur-2xl"></div>
    <div class="relative max-w-3xl space-y-4">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900 md:text-5xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-lg text-slate-600">{{ $subtitle }}</p>
        @endif
        @if($ctaLabel)
            <a href="{{ $ctaUrl }}" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-slate-700">{{ $ctaLabel }}</a>
        @endif
    </div>
</section>
