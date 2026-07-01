@php
    $workspaceItems = [
        ['route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'ph-squares-four', 'label' => 'Dashboard'],
        ['route' => 'admin.content.index', 'active' => 'admin.content.*', 'icon' => 'ph-files', 'label' => 'Content'],
        ['route' => 'admin.blocks.index', 'active' => 'admin.blocks.*', 'icon' => 'ph-cube', 'label' => 'Blocks'],
        ['route' => 'admin.content-types.index', 'active' => 'admin.content-types.*', 'icon' => 'ph-blueprint', 'label' => 'Types'],
        ['route' => 'admin.assets.index', 'active' => 'admin.assets.*', 'icon' => 'ph-image', 'label' => 'Assets'],
        ['route' => 'admin.datasources.index', 'active' => 'admin.datasources.*', 'icon' => 'ph-database', 'label' => 'Datasources'],
    ];

    $adminItems = [
        ['route' => 'admin.spaces.index', 'active' => 'admin.spaces.*', 'icon' => 'ph-stack', 'label' => 'Spaces'],
        ['route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'ph-users', 'label' => 'Users', 'can' => 'manage users'],
        ['route' => 'admin.settings.index', 'active' => 'admin.settings.*', 'icon' => 'ph-gear', 'label' => 'Settings'],
    ];

    $navLinkClasses = function (string $activePattern): string {
        $base = 'group flex h-[30px] items-center gap-2.5 rounded-sm px-2.5 text-sm transition-colors duration-100';

        return request()->routeIs($activePattern)
            ? $base . ' bg-active text-primary font-medium shadow-xs'
            : $base . ' text-secondary hover:bg-hover hover:text-primary';
    };
@endphp

<nav class="h-full min-h-0 bg-app border-r border-subtle flex flex-col shrink-0 z-sidebar" aria-label="Main" style="width: var(--admin-nav-width);">
    <div class="flex h-topbar items-center px-3.5">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-2.5 rounded-sm px-1.5 py-1.5 text-left transition-colors duration-100 hover:bg-hover" wire:navigate title="Pilot CMS">
            <span class="grid h-[26px] w-[26px] shrink-0 place-items-center rounded-sm bg-accent text-on-accent shadow-xs">
                <img src="{{ asset('img/logo.svg') }}" alt="" class="h-[17px] w-[17px] object-contain" />
            </span>
            <span class="min-w-0 flex-1 text-sm font-medium leading-tight text-primary">
                <span class="block truncate">Pilot CMS</span>
                <span class="block truncate text-2xs font-normal text-tertiary">Jaunt workspace</span>
            </span>
            <i class="ph ph-caret-up-down text-sm text-tertiary" aria-hidden="true"></i>
        </a>
    </div>

    <div class="mx-3 mb-2 mt-1">
        <button type="button" x-data x-on:click="$dispatch('open-command-palette')" class="flex h-[30px] w-full items-center gap-2 rounded-sm border border-[color:var(--border-default)] bg-card px-2.5 text-sm text-tertiary shadow-xs transition-colors duration-100 hover:border-strong hover:text-secondary">
            <i class="ph ph-magnifying-glass text-sm" aria-hidden="true"></i>
            <span>Search</span>
            <kbd class="ml-auto rounded-xs border border-subtle bg-sunken px-1.5 py-0.5 font-mono text-2xs text-tertiary">⌘K</kbd>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-2 pb-3">
        <div class="px-2 pb-1 pt-0.5 text-2xs font-semibold uppercase tracking-[0.06em] text-tertiary">Workspace</div>
        <div class="space-y-px">
            @foreach ($workspaceItems as $item)
                @php $isActive = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="{{ $navLinkClasses($item['active']) }}" wire:navigate>
                    <i class="ph {{ $item['icon'] }} text-base {{ $isActive ? 'text-accent' : 'text-tertiary group-hover:text-secondary' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="px-2 pb-1 pt-4 text-2xs font-semibold uppercase tracking-[0.06em] text-tertiary">Admin</div>
        <div class="space-y-px">
            @foreach ($adminItems as $item)
                @continue(isset($item['can']) && auth()->user()->cannot($item['can']))
                @php $isActive = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}" class="{{ $navLinkClasses($item['active']) }}" wire:navigate>
                    <i class="ph {{ $item['icon'] }} text-base {{ $isActive ? 'text-accent' : 'text-tertiary group-hover:text-secondary' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="border-t border-subtle p-2">
        <flux:dropdown position="top" align="start">
            <button type="button" class="flex w-full items-center gap-2.5 rounded-sm p-1.5 text-left transition-colors duration-100 hover:bg-hover" aria-label="User menu">
                <span class="relative inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-accent-subtle text-xs font-semibold text-accent-text">
                    {{ str(auth()->user()->name)->trim()->substr(0, 1)->upper() }}
                    <span class="absolute -bottom-px -right-px h-2 w-2 rounded-full border-2 border-app bg-success"></span>
                </span>
                <span class="min-w-0 flex-1 text-sm font-medium text-primary">
                    <span class="block truncate">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-2xs font-normal text-tertiary">{{ auth()->user()->email }}</span>
                </span>
                <i class="ph ph-gear text-sm text-tertiary" aria-hidden="true"></i>
            </button>
            <flux:menu>
                <div class="p-2 text-sm">
                    <div class="font-medium text-primary">{{ auth()->user()->name }}</div>
                    <div class="text-tertiary text-xs">{{ auth()->user()->email }}</div>
                </div>
                <flux:menu.separator />
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">{{ __('Log Out') }}</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </div>
</nav>
