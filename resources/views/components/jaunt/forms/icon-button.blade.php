{{-- jaunt.forms.icon-button — square, icon-only control. Always pass a label. --}}
@props([
    'label',
    'size' => 'md', // sm(26) md(32) lg(40)
    'solid' => false,
    'active' => false,
])

@php
$sizes = ['sm' => 'w-control-sm h-control-sm', 'md' => 'w-control h-control', 'lg' => 'w-control-lg h-control-lg'];
$solidCls = $solid ? 'bg-card border-[color:var(--border-default)] shadow-xs' : '';
$activeCls = $active ? 'bg-accent-subtle text-accent-text' : '';
@endphp

<button
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center rounded-sm border border-transparent
            text-secondary transition-colors duration-instant ease-standard
            enabled:hover:bg-hover enabled:hover:text-primary
            enabled:active:bg-active enabled:active:scale-[0.94]
            focus-visible:outline-none focus-visible:shadow-ring
            disabled:opacity-40 disabled:cursor-not-allowed
            {$sizes[$size]} {$solidCls} {$activeCls}",
        'type' => 'button',
    ]) }}
    aria-label="{{ $label }}"
    title="{{ $label }}"
    @if ($active) aria-pressed="true" @endif
>
    {{ $slot }}
</button>
