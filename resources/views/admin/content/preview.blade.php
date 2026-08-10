<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pilot-content-id" content="{{ $content->id }}">
    <title>{{ $content->name }} Preview</title>
    @vite(['resources/css/app.css'])
    <style>
        [data-pilot-editable="block"] {
            position: relative;
            outline: 1px solid transparent;
            outline-offset: 3px;
            cursor: pointer;
        }

        [data-pilot-editable="block"]:hover {
            outline-color: rgb(45 212 191);
            background-color: rgb(240 253 250 / 0.45);
        }

        [data-pilot-editable="block"][data-pilot-selected="true"] {
            outline: 2px solid rgb(20 184 166);
            box-shadow: 0 0 0 6px rgb(20 184 166 / 0.12);
        }

        [data-pilot-editable="block"]::before {
            content: attr(data-pilot-component);
            position: absolute;
            top: -28px;
            left: 0;
            z-index: 20;
            display: none;
            border-radius: 6px;
            background: rgb(15 23 42);
            padding: 4px 8px;
            color: white;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }

        [data-pilot-editable="block"]:hover::before,
        [data-pilot-editable="block"][data-pilot-selected="true"]::before {
            display: block;
        }

        .pilot-preview-toolbar {
            position: absolute;
            top: -34px;
            right: 0;
            z-index: 30;
            display: none;
            align-items: center;
            gap: 2px;
            border: 1px solid rgb(226 232 240);
            border-radius: 8px;
            background: white;
            padding: 2px;
            box-shadow: 0 10px 25px rgb(15 23 42 / 0.12);
        }

        [data-pilot-editable="block"]:hover > .pilot-preview-toolbar,
        [data-pilot-editable="block"][data-pilot-selected="true"] > .pilot-preview-toolbar {
            display: flex;
        }

        .pilot-preview-toolbar button {
            display: flex;
            height: 26px;
            min-width: 26px;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            color: rgb(71 85 105);
            font-size: 12px;
            font-weight: 700;
        }

        .pilot-preview-toolbar button:hover {
            background: rgb(240 253 250);
            color: rgb(13 148 136);
        }
    </style>
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
                        class="rounded-lg border border-transparent transition-colors hover:border-blue-300 hover:bg-blue-50/30"
                    >
                        <div class="pilot-preview-toolbar" aria-hidden="true">
                            <button type="button" data-pilot-action="move-up" title="Move up">↑</button>
                            <button type="button" data-pilot-action="move-down" title="Move down">↓</button>
                            <button type="button" data-pilot-action="duplicate" title="Duplicate">⧉</button>
                            <button type="button" data-pilot-action="delete" title="Delete">×</button>
                        </div>
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
