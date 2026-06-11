<nav class="h-full min-h-0 bg-slate-100 border-r border-slate-200 flex flex-col shrink-0 z-40" aria-label="Main" style="width: var(--admin-nav-width);">
    <div class="px-4 pt-4 pb-3 border-b border-slate-200">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3" wire:navigate title="Pilot">
            <span class="w-9 h-9 rounded-lg bg-teal-500/15 border border-teal-400/30 flex items-center justify-center overflow-hidden p-1.5">
                <img src="{{ asset('img/logo.svg') }}" alt="Pilot" class="w-full h-full object-contain" />
            </span>
            <span class="leading-tight">
                <span class="block text-sm font-semibold text-slate-900">Pilot CMS</span>
                <span class="block text-[11px] text-slate-500">Workspace</span>
            </span>
        </a>
    </div>

    <div class="px-3 py-3 border-b border-slate-200">
        <button type="button" x-data x-on:click="$dispatch('open-command-palette')" class="w-full h-9 rounded-md bg-white border border-slate-200 text-slate-500 text-xs px-3 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <span class="flex items-center gap-2">
                <i class="ph ph-magnifying-glass text-sm"></i>
                Search
            </span>
            <span class="text-[10px] text-slate-400">⌘K</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-3 space-y-5">
        <div>
            <p class="px-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-slate-400 mb-2">Workspace</p>
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-squares-four text-base"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.content.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.content.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-files text-base"></i>
                    <span>Content</span>
                </a>
                <a href="{{ route('admin.blocks.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.blocks.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-cube text-base"></i>
                    <span>Blocks</span>
                </a>
                <a href="{{ route('admin.content-types.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.content-types.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-blueprint text-base"></i>
                    <span>Types</span>
                </a>
                <a href="{{ route('admin.assets.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.assets.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-image text-base"></i>
                    <span>Assets</span>
                </a>
                <a href="{{ route('admin.datasources.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.datasources.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-database text-base"></i>
                    <span>Datasources</span>
                </a>
            </div>
        </div>

        <div>
            <p class="px-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-slate-400 mb-2">Admin</p>
            <div class="space-y-1">
                <a href="{{ route('admin.spaces.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.spaces.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-stack text-base"></i>
                    <span>Spaces</span>
                </a>
                @can('manage users')
                <a href="{{ route('admin.users.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-users text-base"></i>
                    <span>Users</span>
                </a>
                @endcan
                <a href="{{ route('admin.settings.index') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-gear text-base"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-200 p-3">
        <flux:dropdown position="top" align="start">
            <button type="button" class="w-full rounded-md px-2 py-2 flex items-center gap-2 text-left hover:bg-slate-200/70 transition-colors" aria-label="User menu">
                <span class="w-7 h-7 rounded-full bg-gradient-to-tr from-teal-400 to-blue-500 border border-slate-200 shrink-0"></span>
                <span class="min-w-0">
                    <span class="block text-xs font-medium text-slate-800 truncate">{{ auth()->user()->name }}</span>
                    <span class="block text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</span>
                </span>
                <i class="ph ph-caret-up-down ml-auto text-slate-400"></i>
            </button>
            <flux:menu>
                <div class="p-2 text-sm">
                    <div class="font-medium text-gray-900">{{ auth()->user()->name }}</div>
                    <div class="text-gray-500 text-xs">{{ auth()->user()->email }}</div>
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
