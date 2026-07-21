<div class="flex flex-col w-full min-w-0 h-full bg-gray-50">
    <header class="cms-topbar" aria-label="Page header">
        <div class="min-w-0">
            <h1 class="cms-title">Edit Space</h1>
            <p class="cms-subtitle">Update workspace settings.</p>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">

    <main class="flex-1 min-w-0 overflow-y-auto">
        <div class="w-full p-6 md:p-8">
            <div class="max-w-2xl">
                <div class="mb-8">
                    <a href="{{ route('admin.spaces.index') }}" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 mb-4 transition-colors" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Spaces
                    </a>
                    <flux:heading>Edit Space</flux:heading>
                </div>

                <form wire:submit="save" class="space-y-6">
                <flux:card>
                    <div class="space-y-6">
                        <flux:field>
                            <flux:label>Name</flux:label>
                            <flux:input wire:model="name" placeholder="My Space" />
                            <flux:error name="name" />
                            <flux:description>The display name for this space</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>Slug</flux:label>
                            <flux:input wire:model="slug" placeholder="my-space" />
                            <flux:error name="slug" />
                            <flux:description>Used in URLs and API endpoints</flux:description>
                        </flux:field>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="space-y-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <flux:heading size="md">Preview URLs</flux:heading>
                                <flux:text class="mt-1 text-sm text-slate-500">Add live frontend URLs that editors can preview against.</flux:text>
                            </div>
                            <flux:button type="button" wire:click="addPreviewTarget" variant="ghost" size="sm">
                                <flux:icon.plus class="size-4" />
                                Add URL
                            </flux:button>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <i class="ph ph-key"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-amber-950">Frontend preview secret</p>
                                    <p class="mt-1 text-sm text-amber-900">Copy this line into the <span class="font-mono">.env</span> file for every frontend Laravel app that should render live previews for this space, then run <span class="font-mono">php artisan optimize:clear</span> in that frontend app.</p>
                                    <div class="mt-3 rounded-md border border-amber-200 bg-white p-3">
                                        <code class="break-all text-xs font-semibold text-slate-900">PILOT_PREVIEW_SECRET={{ $previewSecret }}</code>
                                    </div>
                                    <p class="mt-2 text-xs text-amber-800">Pilot uses this same value to sign preview links. If the frontend app has a different value, preview URLs will return 403.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            @forelse($previewTargets as $index => $target)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1.5fr_auto]">
                                        <flux:field>
                                            <flux:label>Name</flux:label>
                                            <flux:input wire:model="previewTargets.{{ $index }}.name" placeholder="Production" />
                                            <flux:error name="previewTargets.{{ $index }}.name" />
                                        </flux:field>

                                        <flux:field>
                                            <flux:label>URL</flux:label>
                                            <flux:input wire:model="previewTargets.{{ $index }}.url" placeholder="https://mysite.test" />
                                            <flux:error name="previewTargets.{{ $index }}.url" />
                                        </flux:field>

                                        <div class="flex items-end gap-2">
                                            <button type="button" wire:click="markDefaultPreviewTarget({{ $index }})" class="h-10 rounded-md border px-3 text-sm font-medium {{ ! empty($target['is_default']) ? 'border-teal-200 bg-teal-50 text-teal-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50' }}">
                                                Default
                                            </button>
                                            <button type="button" wire:click="removePreviewTarget({{ $index }})" class="flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-red-500" aria-label="Remove preview URL">
                                                <i class="ph ph-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border-2 border-dashed border-slate-200 p-8 text-center">
                                    <p class="text-sm font-medium text-slate-700">No preview URLs configured</p>
                                    <p class="mt-1 text-sm text-slate-500">Add Production, Staging, or Local URLs for live previews.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </flux:card>

                <div class="flex items-center justify-end gap-3">
                    <flux:button href="{{ route('admin.spaces.index') }}" wire:navigate variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
                </form>
            </div>
        </div>
    </main>

    <aside class="w-[var(--admin-rail-width)] shrink-0 bg-white border-l border-slate-200 flex flex-col shadow-xl overflow-hidden z-20" aria-label="Details">
        <div class="h-14 border-b border-slate-200 flex items-center px-5 bg-white shrink-0">
            <h2 class="text-sm font-bold text-slate-800">Details</h2>
        </div>
        <div class="flex-1 overflow-y-auto p-5 text-sm text-slate-500">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Preview URL format</p>
                    <p class="mt-2">Pilot will open the selected frontend URL at:</p>
                    <p class="mt-2 rounded bg-slate-50 p-2 font-mono text-xs text-slate-700">/_pilot/preview/{content}</p>
                </div>
                <p>The frontend app must have the <span class="font-mono">pilot/laravel</span> package installed and share the same preview secret.</p>
                <p>After copying the secret to the frontend app, open a fresh preview URL from the page editor. Older links may have been signed with a previous value.</p>
            </div>
        </div>
    </aside>
    </div>
</div>
