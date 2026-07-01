{{-- jaunt.forms.button — see components-source/components/forms/Button.{jsx,d.ts,prompt.md} for the source contract. --}}
@props([
    'variant' => 'primary', // primary | secondary | ghost | danger | ai
    'size' => 'md',         // sm (26px) | md (32px) | lg (40px)
    'as' => 'button',       // button | a
    'block' => false,
    'loading' => false,
    'iconLeft' => null,
    'iconRight' => null,
])

@php
$variants = [
    'primary'   => 'bg-accent text-on-accent hover:bg-accent-hover active:bg-accent-active',
    'secondary' => 'bg-card text-primary border-[color:var(--border-default)] shadow-xs hover:bg-hover hover:border-strong active:bg-active',
    'ghost'     => 'text-secondary hover:bg-hover hover:text-primary active:bg-active',
    'danger'    => 'bg-danger text-white hover:brightness-[1.06] focus-visible:shadow-ring-danger',
    'ai'        => 'bg-ai-subtle text-ai-text border border-ai-border hover:border-ai focus-visible:shadow-ring-ai',
];
$sizes = [
    'sm' => 'h-control-sm px-2.5 text-xs font-medium gap-1.5',
    'md' => 'h-control px-3.5 text-sm font-medium gap-inline',
    'lg' => 'h-control-lg px-[18px] text-base font-medium gap-inline',
];
$tag = $as === 'a' ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center rounded-sm border border-transparent
            whitespace-nowrap select-none cursor-pointer
            transition-colors duration-instant ease-standard active:scale-[0.98]
            focus-visible:outline-none focus-visible:shadow-ring
            disabled:opacity-50 disabled:pointer-events-none disabled:active:scale-100
            {$variants[$variant]} {$sizes[$size]}" . ($block ? ' flex w-full' : ''),
    ]) }}
    @if ($tag === 'button' && !isset($attributes['type'])) type="button" @endif
    @if ($loading) disabled aria-busy="true" @endif
>
    @if ($loading)
        <span class="w-3.5 h-3.5 rounded-full border-2 border-current border-t-transparent animate-spin" aria-hidden="true"></span>
    @else
        {{ $iconLeft }}
    @endif
    {{ $slot }}
    @unless ($loading)
        {{ $iconRight }}
    @endunless
</{{ $tag }}>
