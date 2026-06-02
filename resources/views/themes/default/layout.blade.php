<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content->meta['meta_title'] ?? $content->name }} · {{ config('app.name') }}</title>
    @if(!empty($content->meta['meta_description']))
        <meta name="description" content="{{ $content->meta['meta_description'] }}">
    @endif
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="text-sm font-semibold tracking-wide text-slate-900">{{ $space->name }}</a>
            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-500">Theme: {{ ucfirst($theme) }}</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-6 py-10">
        @yield('content')
    </main>
</body>
</html>
