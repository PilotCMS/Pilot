@php
    $content = $data['content'] ?? '<p>Rich text content</p>';
    $content = is_array($content) ? ($content['en'] ?? reset($content) ?: '<p>Rich text content</p>') : $content;
@endphp

<div class="prose max-w-none prose-p:leading-relaxed prose-headings:mt-6 prose-headings:mb-4 first:prose-p:mt-0 last:prose-p:mb-0">
    {!! $content !!}
</div>
