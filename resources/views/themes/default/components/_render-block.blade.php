@php
    $componentView = 'themes.default.components.' . $block['component'];
@endphp

@if(view()->exists($componentView))
    @include($componentView, ['block' => $block])
@else
    @include('themes.default.components.fallback', ['block' => $block])
@endif
