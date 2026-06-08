@php
    $componentView = 'themes.marketing.components.' . $block['component'];
@endphp

{!! $block['editor']['comment'] ?? '' !!}
<div {!! $block['editor']['attributes'] ?? '' !!}>
    @if(view()->exists($componentView))
        @include($componentView, ['block' => $block])
    @else
        @include('themes.marketing.components.fallback', ['block' => $block])
    @endif
</div>
