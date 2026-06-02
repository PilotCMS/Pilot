@php
    $content = $block['data']['content'] ?? '<p>Rich text content</p>';
@endphp

<section class="rounded-3xl border border-slate-200 bg-white p-10 shadow-sm">
    <article class="prose prose-slate max-w-none prose-headings:font-semibold prose-a:text-teal-700">
        {!! $content !!}
    </article>
</section>
