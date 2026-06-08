@php
    $componentView = 'themes.default.components.' . $block['component'];
@endphp

{!! $block['editor']['comment'] ?? '' !!}
<div {!! $block['editor']['attributes'] ?? '' !!}>
    @if(view()->exists($componentView))
        @include($componentView, ['block' => $block])
    @else
        @include('themes.default.components.fallback', ['block' => $block])
    @endif
</div>
