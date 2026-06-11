<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pilot-content-id" content="{{ $content->id }}">
    <title>{{ $content->name }} Preview</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased" data-pilot-content-id="{{ $content->id }}">
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
                        $componentName = (string) str($block->type)->replace(['.', '/', '\\'], '-')->kebab();
                        $componentView = 'components.' . $componentName;
                        $data = $block->data ?? [];
                        $children = $block->children ?? [];
                    @endphp
                    <section
                        data-preview-block="{{ $block->id }}"
                        data-pilot-editable="block"
                        data-pilot-block-id="{{ $block->id }}"
                        data-pilot-component="{{ $block->type }}"
                        data-pilot-component-path="{{ $content->type }}/{{ $block->type }}"
                        class="rounded-lg border border-transparent transition-colors hover:border-teal-300 hover:bg-teal-50/30"
                    >
                        @if(view()->exists($componentView))
                            <x-dynamic-component :component="$componentName" :block="$block" :data="$data" :children="$children" />
                        @else
                            <x-fallback :block="$block" :data="$data" :children="$children" />
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </main>

    @includeIf('pilot::editor-bridge')
    @includeIf('pilot::in-context')
</body>
</html>
