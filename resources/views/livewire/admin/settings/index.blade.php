<div class="flex h-full w-full min-w-0 flex-col bg-gray-50">
    <header class="z-30 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-6 shadow-sm" aria-label="Page header">
        <div>
            <h1 class="text-lg font-bold tracking-tight text-slate-900">CMS Settings</h1>
            <p class="mt-0.5 text-xs text-slate-500">Configure public rendering, API access, and preview behavior</p>
        </div>

        <div class="flex items-center gap-3">
            <flux:button wire:click="resetToEnvironmentDefaults" wire:confirm="Reset CMS settings to environment defaults?" variant="ghost" size="sm">
                Reset
            </flux:button>
            <flux:button type="submit" form="cms-settings-form" variant="primary" size="sm">
                Save settings
            </flux:button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
        <main class="min-w-0 flex-1 overflow-y-auto">
            <div class="w-full space-y-8 p-6 md:p-8">
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Default space</p>
                        <p class="mt-2 text-xl font-bold text-slate-900">{{ $defaultSpace ?: 'First space' }}</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Preview lifetime</p>
                        <p class="mt-2 text-xl font-bold text-slate-900">{{ $previewExpirationMinutes }} minutes</p>
                    </div>
                </section>

                <form id="cms-settings-form" wire:submit="save" class="space-y-8">
                    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <flux:heading size="md">Public website</flux:heading>
                            <flux:text class="mt-1 text-sm text-slate-500">Control which content space and entry point power the public routes.</flux:text>
                        </div>

                        <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2">
                            <flux:field>
                                <flux:label>Default Space</flux:label>
                                <flux:select wire:model="defaultSpace">
                                    <option value="">First space in database</option>
                                    @foreach($spaces as $space)
                                        <option value="{{ $space->slug }}">{{ $space->name }} ({{ $space->slug }})</option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="defaultSpace" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Home Slug</flux:label>
                                <flux:input wire:model="homeSlug" placeholder="home" />
                                <flux:error name="homeSlug" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Default Locale</flux:label>
                                <flux:input wire:model="defaultLocale" placeholder="en" />
                                <flux:error name="defaultLocale" />
                            </flux:field>
                        </div>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <flux:heading size="md">API & preview</flux:heading>
                            <flux:text class="mt-1 text-sm text-slate-500">Set the guardrails for draft content delivery and editor preview links.</flux:text>
                        </div>

                        <div class="space-y-5 p-5">
                            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                                    <flux:checkbox wire:model="draftApiEnabled" />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Allow draft API responses</span>
                                        <span class="mt-1 block text-sm text-slate-500">Authenticated API consumers can request `version=draft` when this is enabled.</span>
                                    </span>
                                </label>

                                <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
                                    <flux:checkbox wire:model="previewLinksEnabled" />
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900">Allow signed preview links</span>
                                        <span class="mt-1 block text-sm text-slate-500">Editor preview endpoints return content only while this is enabled.</span>
                                    </span>
                                </label>
                            </div>

                            <flux:field class="max-w-xs">
                                <flux:label>Preview Expiration Minutes</flux:label>
                                <flux:input type="number" min="5" max="10080" wire:model="previewExpirationMinutes" />
                                <flux:error name="previewExpirationMinutes" />
                            </flux:field>
                        </div>
                    </section>
                </form>
            </div>
        </main>

        <aside class="z-20 flex w-[var(--admin-rail-width)] shrink-0 flex-col overflow-hidden border-l border-slate-200 bg-white shadow-xl" aria-label="Details">
            <div class="flex h-14 shrink-0 items-center px-5 border-b border-slate-200 bg-white">
                <h2 class="text-sm font-bold text-slate-800">Effective settings</h2>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto p-5">
                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Public routes</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Home path</dt>
                            <dd class="font-mono text-slate-900">/{{ $homeSlug }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Page view</dt>
                            <dd class="font-mono text-slate-900">{{ config('pilot.views.page', 'page') }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">Locale fallback</dt>
                            <dd class="font-mono text-slate-900">{{ $defaultLocale }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Delivery controls</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-sm text-slate-600">Draft API</span>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $draftApiEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $draftApiEnabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span class="text-sm text-slate-600">Preview links</span>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $previewLinksEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $previewLinksEnabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stored overrides</h3>
                    @if($settings->isEmpty())
                        <p class="text-sm text-slate-500">No database overrides are saved. Pilot is using environment defaults.</p>
                    @else
                        <div class="space-y-2">
                            @foreach($settings as $setting)
                                <div class="rounded-lg border border-slate-200 p-3">
                                    <p class="font-mono text-xs font-semibold text-slate-800">{{ $setting->key }}</p>
                                    <p class="mt-1 break-all font-mono text-xs text-slate-500">{{ json_encode($setting->value['value'] ?? null) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </aside>
    </div>
</div>
