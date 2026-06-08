<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $content->meta['meta_title'] ?? $content->name }} · {{ config('app.name') }}</title>
    @if(!empty($content->meta['meta_description']))
        <meta name="description" content="{{ $content->meta['meta_description'] }}">
    @endif
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-white text-slate-900 antialiased">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#ecfeff_0%,_#ffffff_35%)]">
        <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-slate-900">{{ $space?->name ?? config('app.name') }}</a>
                <div class="flex items-center gap-3">
                    <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700">Public Theme</span>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">{{ ucfirst($theme) }}</span>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-6 py-12">
            @yield('content')
        </main>
    </div>
    @include('cms.editor-bridge')
    @auth
        @if(config('cms.frontend_editor.enabled', true))
            <script src="{{ route('cms.frontend-editor.script') }}"></script>
        @endif
    @endauth
</body>
</html>
