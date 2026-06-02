<div class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">{{ $block['type'] ?? $block->type ?? 'block' }}</h3>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Fallback preview</span>
    </div>
    <div class="grid gap-2 text-sm text-slate-700">
        @foreach(($data ?? []) as $key => $value)
            <div class="rounded border border-slate-100 bg-slate-50 px-2.5 py-1.5">
                <span class="font-medium text-slate-500">{{ $key }}:</span>
                <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
            </div>
        @endforeach
    </div>
</div>
