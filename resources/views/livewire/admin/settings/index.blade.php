<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">CMS Settings</h1>
            <p class="text-xs text-slate-500 mt-0.5">Configure your Pilot instance</p>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
    <div class="mb-6">
        <flux:heading>CMS Settings</flux:heading>
        <flux:text class="text-muted-foreground text-sm mt-1">Configure your Pilot instance</flux:text>
    </div>

    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30 py-20 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/40">
            <flux:icon.cog-6-tooth class="size-8 text-amber-600 dark:text-amber-400" />
        </div>
        <flux:heading size="md" class="mt-6">Coming soon</flux:heading>
        <flux:text class="mt-3 max-w-md text-center text-sm text-muted-foreground">
            CMS settings will let you configure API keys, default spaces, publishing workflows, and other global options for your content management.
        </flux:text>
        <flux:text class="mt-2 text-xs text-muted-foreground">
            This feature is in development. Check back soon.
        </flux:text>
        <a href="{{ route('profile.edit') }}" class="mt-6 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400" wire:navigate>
            Go to profile settings →
        </a>
    </div>
        </div>
    </main>
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0"><h2 class="text-sm font-bold text-slate-800">Details</h2></div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500 flex items-center justify-center"><p>Select an option.</p></div>
    </aside>
    </div>
</div>
