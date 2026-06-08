@php
    $title = $block['data']['title'] ?? 'Ready to get started?';
    $buttonText = $block['data']['button_text'] ?? 'Learn more';
    $buttonUrl = $block['data']['button_url'] ?? '#';
    $style = $block['data']['style'] ?? 'primary';
    $buttonClasses = match ($style) {
        'secondary' => 'bg-white text-slate-900 ring-1 ring-slate-200 hover:bg-slate-50',
        'outline' => 'bg-transparent text-slate-900 ring-1 ring-slate-300 hover:bg-white/70',
        default => 'bg-slate-900 text-white hover:bg-slate-700',
    };
@endphp

<section class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-teal-50 to-cyan-50 p-8 shadow-sm">
    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Call to action</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $title }}</h2>
        </div>
        <a href="{{ $buttonUrl }}" class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors {{ $buttonClasses }}">
            {{ $buttonText }}
        </a>
    </div>
</section>
