@props([
    'block' => [],
    'data' => [],
    'children' => [],
    'renderChildren' => true,
])

<x-columns :block="$block" :data="$data" :children="$children" :render-children="$renderChildren" />
