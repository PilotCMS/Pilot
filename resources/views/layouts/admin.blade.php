<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
        @stack('admin-head')
    </head>
    <body class="admin-app h-full min-h-screen w-full flex bg-app text-primary overflow-hidden font-sans antialiased selection:bg-accent-subtle selection:text-accent-text" style="--admin-nav-width: var(--sidebar-w); --admin-rail-width: clamp(320px, 22vw, 420px);">
        <script src="https://unpkg.com/@phosphor-icons/web" defer></script>

        @include('partials.admin-nav')
        <livewire:admin.command-palette />
        <x-toast-region />

        {{-- Slot: sits next to workspace sidebar, flex-1, full height --}}
        <div class="flex-1 flex min-w-0 min-h-0 overflow-hidden" data-flux-main>
            <div class="flex-1 min-w-0 min-h-0 flex flex-col">
                {{ $slot }}
            </div>
        </div>

        @fluxScripts
    </body>
</html>
