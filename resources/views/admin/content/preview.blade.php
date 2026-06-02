<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content->name }} Preview</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto max-w-6xl p-8 lg:p-12">
        @if($blocks->isEmpty())
            <div class="rounded-xl border-2 border-dashed border-slate-200 bg-white p-14 text-center text-slate-500">
                <p class="text-lg font-semibold text-slate-700">No blocks yet</p>
                <p class="mt-1 text-sm">Add blocks from the editor to preview this page.</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($blocks as $block)
                    @php
                        $blockView = 'blocks.' . $block->type;
                    @endphp
                    <section
                        data-preview-block="{{ $block->id }}"
                        class="rounded-lg border border-transparent transition-colors hover:border-teal-300 hover:bg-teal-50/30"
                    >
                        @if(view()->exists($blockView))
                            @include($blockView, ['block' => $block, 'data' => $block->data ?? []])
                        @else
                            @include('blocks._fallback', ['block' => $block, 'data' => $block->data ?? []])
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </main>

    <script>
        document.querySelectorAll('[data-preview-block]').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                window.parent.postMessage({
                    type: 'pilot-preview-select-block',
                    blockId: Number(element.dataset.previewBlock),
                }, window.location.origin);
            });
        });
    </script>
</body>
</html>
