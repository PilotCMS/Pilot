<div class="grid gap-8 lg:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="min-w-0">
        <div class="space-y-1">
            <a href="{{ route('profile.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('profile.edit') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                <i class="ph ph-user-circle text-base"></i>
                <span>{{ __('Profile') }}</span>
            </a>
            <a href="{{ route('user-password.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('user-password.edit') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                <i class="ph ph-lock-key text-base"></i>
                <span>{{ __('Password') }}</span>
            </a>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <a href="{{ route('two-factor.show') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('two-factor.show') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                    <i class="ph ph-shield-check text-base"></i>
                    <span>{{ __('Two-Factor Auth') }}</span>
                </a>
            @endif
            <a href="{{ route('appearance.edit') }}" class="h-9 rounded-md px-2.5 text-sm flex items-center gap-2.5 transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-white border border-slate-200 text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/70' }}" wire:navigate>
                <i class="ph ph-palette text-base"></i>
                <span>{{ __('Appearance') }}</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 self-stretch min-w-0">
        <div>
            <h2 class="text-base font-semibold text-slate-900">{{ $heading ?? '' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $subheading ?? '' }}</p>
        </div>

        <div class="mt-6 w-full max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</div>
