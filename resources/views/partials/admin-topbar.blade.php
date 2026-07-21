@php
    $workspaceName = 'Pilot CMS';

    $currentLabel = match (true) {
        request()->routeIs('admin.dashboard') => 'Dashboard',
        request()->routeIs('admin.content.index') => 'Content',
        request()->routeIs('admin.content.create') => 'New content',
        request()->routeIs('admin.content-types.*') => 'Content types',
        request()->routeIs('admin.blocks.create') => 'New block type',
        request()->routeIs('admin.blocks.edit') => 'Edit block type',
        request()->routeIs('admin.blocks.*') => 'Blocks',
        request()->routeIs('admin.assets.*') => 'Assets',
        request()->routeIs('admin.datasources.*') => 'Datasources',
        request()->routeIs('admin.spaces.create') => 'New space',
        request()->routeIs('admin.spaces.edit') => 'Edit space',
        request()->routeIs('admin.spaces.*') => 'Spaces',
        request()->routeIs('admin.users.*') => 'Users',
        request()->routeIs('admin.settings.*') => 'Settings',
        default => 'Dashboard',
    };
@endphp

<header class="cms-global-topbar" aria-label="Application toolbar">
    <nav class="cms-breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}" wire:navigate>{{ $workspaceName }}</a>
        <i class="ph ph-caret-right" aria-hidden="true"></i>
        <span aria-current="page">{{ $currentLabel }}</span>
    </nav>

    <div class="cms-global-actions">
        <button
            type="button"
            x-data
            x-on:click="$dispatch('open-command-palette')"
            class="cms-iconbtn"
            aria-label="Search"
            title="Search (⌘K)"
        >
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
        </button>

        <button type="button" class="cms-iconbtn" aria-label="Notifications" title="Notifications">
            <i class="ph ph-bell" aria-hidden="true"></i>
        </button>

        <button
            type="button"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            x-on:click="window.Flux.applyAppearance(dark ? 'light' : 'dark')"
            x-on:pilot-theme-changed.window="dark = $event.detail.isDark"
            x-on:livewire:navigated.window="dark = document.documentElement.classList.contains('dark')"
            class="cms-iconbtn"
            x-bind:aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
            x-bind:title="dark ? 'Light mode' : 'Dark mode'"
        >
            <i class="ph" x-bind:class="dark ? 'ph-sun' : 'ph-moon'" aria-hidden="true"></i>
        </button>
    </div>
</header>
