@if($blocks->isEmpty())
    <section class="rounded-3xl border-2 border-dashed border-slate-200 bg-white p-16 text-center shadow-sm">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">No published blocks for this page yet.</h1>
        <p class="mt-3 text-sm text-slate-500">Add and publish content from the CMS editor to populate this theme.</p>
    </section>
@else
    <div class="space-y-12">
        @foreach($blocks as $block)
            @include('themes.marketing.components._render-block', ['block' => $block])
        @endforeach
    </div>
@endif
