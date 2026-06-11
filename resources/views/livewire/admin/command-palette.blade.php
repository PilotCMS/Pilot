<div
    x-data="{
        open: false,
        activeIndex: 0,
        openPalette() {
            this.open = true;
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.search?.focus());
        },
        closePalette() {
            this.open = false;
            this.$wire.set('search', '');
        },
        results() {
            return Array.from(this.$el.querySelectorAll('[data-command-result]'));
        },
        move(delta) {
            const results = this.results();

            if (results.length === 0) {
                return;
            }

            this.activeIndex = (this.activeIndex + delta + results.length) % results.length;
            results[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
        },
        choose() {
            this.results()[this.activeIndex]?.click();
        },
    }"
    x-on:open-command-palette.window="openPalette()"
    x-on:keydown.window="
        if (($event.metaKey || $event.ctrlKey) && $event.key.toLowerCase() === 'k') {
            $event.preventDefault();
            openPalette();
        }

        if (open && $event.key === 'Escape') {
            $event.preventDefault();
            closePalette();
        }
    "
>
    <div
        x-cloak
        x-show="open"
        class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/25 px-4 pt-[12vh] backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="command-palette-title"
        x-transition.opacity
        wire:ignore.self
    >
        <button type="button" class="absolute inset-0 cursor-default" aria-label="Close search" x-on:click="closePalette()"></button>

        <div
            class="relative z-10 w-full max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl ring-1 ring-slate-950/5"
            x-on:click.outside="closePalette()"
            x-transition.scale.origin.top.duration.150ms
        >
            <h2 id="command-palette-title" class="sr-only">Command search</h2>

            <div class="flex h-14 items-center gap-3 border-b border-slate-200 px-4">
                <i class="ph ph-magnifying-glass text-lg text-slate-400"></i>
                <input
                    x-ref="search"
                    type="search"
                    wire:model.live.debounce.150ms="search"
                    x-on:input="activeIndex = 0"
                    x-on:keydown.arrow-down.prevent="move(1)"
                    x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="choose()"
                    placeholder="Search content, assets, settings..."
                    class="h-full flex-1 border-0 bg-transparent p-0 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0"
                />
                <button type="button" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-500 hover:bg-slate-50" x-on:click="closePalette()">
                    Esc
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto p-2">
                @forelse ($groups as $group)
                    <div class="py-2 first:pt-0">
                        <div class="px-2 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                            {{ $group['label'] }}
                        </div>

                        <div class="space-y-1">
                            @foreach ($group['results'] as $result)
                                <a
                                    href="{{ $result['url'] }}"
                                    wire:navigate
                                    data-command-result
                                    x-on:mouseenter="activeIndex = Array.from($el.closest('[role=dialog]').querySelectorAll('[data-command-result]')).indexOf($el)"
                                    x-bind:class="activeIndex === Array.from($el.closest('[role=dialog]').querySelectorAll('[data-command-result]')).indexOf($el) ? 'bg-teal-50 text-slate-950 ring-1 ring-teal-100' : 'text-slate-700 hover:bg-slate-50'"
                                    class="group flex min-h-14 items-center gap-3 rounded-lg px-3 py-2.5 outline-none transition-colors"
                                >
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 group-hover:text-slate-700">
                                        <i class="ph {{ $result['icon'] }} text-base"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $result['title'] }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $result['description'] }}</span>
                                    </span>
                                    <i class="ph ph-arrow-right text-sm text-slate-300"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400">
                            <i class="ph ph-magnifying-glass text-lg"></i>
                        </div>
                        <p class="mt-3 text-sm font-medium text-slate-800">No results found</p>
                        <p class="mt-1 text-xs text-slate-500">Try a page title, asset name, block key, or admin section.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
