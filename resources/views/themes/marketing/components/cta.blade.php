@php
    $title = $block['data']['title'] ?? 'Ready to launch?';
    $buttonText = $block['data']['button_text'] ?? 'Start now';
    $buttonUrl = $block['data']['button_url'] ?? '#';
    $style = $block['data']['style'] ?? 'primary';
    $buttonClasses = match ($style) {
        'secondary' => 'bg-teal-400 text-slate-950 hover:bg-teal-300',
        'outline' => 'bg-transparent text-white ring-1 ring-white/35 hover:bg-white/10',
        default => 'bg-white text-slate-950 hover:bg-slate-100',
    };
@endphp

<section class="rounded-3xl bg-slate-950 p-8 text-white shadow-2xl">
    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-300">Call to action</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">{{ $title }}</h2>
        </div>
        <a href="{{ $buttonUrl }}" class="inline-flex shrink-0 items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors {{ $buttonClasses }}">
            {{ $buttonText }}
        </a>
    </div>
</section>
