<section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-amber-900">{{ $block['component'] }}</h2>
        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] text-amber-700">Missing theme component</span>
    </div>

    @if(!empty($block['data']))
        <dl class="grid gap-2 text-sm text-amber-900">
            @foreach($block['data'] as $key => $value)
                <div class="rounded border border-amber-200 bg-white/80 px-2.5 py-1.5">
                    <dt class="inline font-medium">{{ $key }}:</dt>
                    <dd class="inline">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</section>
