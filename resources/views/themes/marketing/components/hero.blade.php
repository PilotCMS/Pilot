@php
    $title = $block['data']['title'] ?? 'Build fast with Pilot';
    $subtitle = $block['data']['subtitle'] ?? null;
    $ctaLabel = $block['data']['cta_label'] ?? 'Get Started';
    $ctaUrl = $block['data']['cta_url'] ?? '#';
@endphp

<section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 px-10 py-14 text-white shadow-2xl">
    <div class="absolute -right-16 top-6 h-44 w-44 rounded-full bg-teal-400/25 blur-3xl"></div>
    <div class="absolute -left-20 -bottom-14 h-56 w-56 rounded-full bg-cyan-500/20 blur-3xl"></div>

    <div class="relative max-w-3xl">
        <p class="inline-flex rounded-full border border-white/20 px-3 py-1 text-xs font-medium text-teal-200">Example Marketing Theme</p>
        <h1 class="mt-5 text-4xl font-semibold tracking-tight md:text-6xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-4 text-lg text-slate-300">{{ $subtitle }}</p>
        @endif
        <a href="{{ $ctaUrl }}" class="mt-7 inline-flex items-center rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 transition-colors hover:bg-slate-100">{{ $ctaLabel }}</a>
    </div>
</section>
