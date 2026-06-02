<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-30 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Datasources</h1>
            <p class="text-xs text-slate-500 mt-0.5">Connect external data to your content</p>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">
    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
    <div class="mb-6">
        <flux:heading>Datasources</flux:heading>
        <flux:text class="text-muted-foreground text-sm mt-1">Connect external data to your content</flux:text>
    </div>

    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30 py-20 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-100 dark:bg-violet-900/40">
            <flux:icon.table-cells class="size-8 text-violet-600 dark:text-violet-400" />
        </div>
        <flux:heading size="md" class="mt-6">Coming soon</flux:heading>
        <flux:text class="mt-3 max-w-md text-center text-sm text-muted-foreground">
            Datasources will let you pull in data from APIs, databases, or external services and use it dynamically in your pages and blocks.
        </flux:text>
        <flux:text class="mt-2 text-xs text-muted-foreground">
            This feature is in development. Check back soon.
        </flux:text>
    </div>
        </div>
    </main>
    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0"><h2 class="text-sm font-bold text-slate-800">Details</h2></div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500 flex items-center justify-center"><p>Select a datasource.</p></div>
    </aside>
    </div>
</div>
