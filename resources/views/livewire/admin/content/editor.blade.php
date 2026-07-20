<div
    x-data="{
        rightPanelTab: @entangle('rightPanelTab'),
        savedJustNow: @entangle('savedJustNow'),
        blockLibraryOpen: @entangle('blockLibraryOpen'),
        selectedBlockId: @entangle('selectedBlockId'),
        canvasMode: 'preview',
        previewDevice: 'desktop',
        previewTargetOrigins: @js($this->previewTargetOrigins),
        previewFrameSrc: @js($this->previewFrameUrl),
        pendingPreviewScroll: null,
        pendingPreviewScrollBlockId: null,
        saveState: @entangle('saveState'),
        conflictMessage: @entangle('conflictMessage'),
        drawerOpen: @entangle('drawerOpen'),
        leftSidebarCollapsed: @entangle('leftSidebarCollapsed'),
        previewWidth() {
            const desktopWidth = ! this.drawerOpen && this.leftSidebarCollapsed
                ? 'min(100%, 1600px)'
                : 'min(100%, 1280px)';

            return {
                desktop: desktopWidth,
                tablet: '768px',
                mobile: '390px'
            }[this.previewDevice];
        },
        previewFrameOrigin() {
            try {
                return new URL(this.$refs.previewFrame?.src || window.location.href).origin;
            } catch (error) {
                return '*';
            }
        },
        postToPreview(message) {
            const frame = this.$refs.previewFrame;

            if (! frame?.contentWindow) {
                return;
            }

            frame.contentWindow.postMessage(message, this.previewFrameOrigin());
        },
        applyPreviewSelectionDirectly() {
            const frame = this.$refs.previewFrame;

            try {
                const doc = frame?.contentDocument;

                if (! doc) {
                    return;
                }

                doc.querySelectorAll('[data-pilot-selected=true]').forEach((element) => {
                    element.removeAttribute('data-pilot-selected');
                });

                if (! this.selectedBlockId) {
                    return;
                }

                const selected = doc.querySelector(`[data-pilot-editable=block][data-pilot-block-id='${Number(this.selectedBlockId)}']`);

                if (selected) {
                    selected.setAttribute('data-pilot-selected', 'true');
                }
            } catch (error) {
                // Cross-origin preview targets are handled by the postMessage bridge.
            }
        },
        syncPreviewEditorMode() {
            this.postToPreview({
                type: 'pilot-preview-editor-mode',
                inContextPanel: false,
            });
        },
        syncPreviewWorkspace() {
            this.syncPreviewEditorMode();
            window.setTimeout(() => this.syncPreviewEditorMode(), 100);
            window.setTimeout(() => this.syncPreviewEditorMode(), 500);
        },
        postPreviewSelection() {
            this.postToPreview({
                type: 'pilot-preview-sync-selected-block',
                blockId: this.selectedBlockId ? Number(this.selectedBlockId) : null,
            });
            this.applyPreviewSelectionDirectly();
        },
        syncPreviewSelection() {
            this.syncPreviewWorkspace();
            this.postPreviewSelection();
            window.setTimeout(() => this.postPreviewSelection(), 100);
            window.setTimeout(() => this.postPreviewSelection(), 500);
        },
        refreshPreviewFrame(url) {
            if (! url || this.previewFrameSrc === url) {
                return;
            }

            this.pendingPreviewScrollBlockId = this.selectedBlockId ? Number(this.selectedBlockId) : null;
            this.capturePreviewScroll();
            this.previewFrameSrc = url;
        },
        previewScrollPosition() {
            const frame = this.$refs.previewFrame;

            try {
                const frameWindow = frame?.contentWindow;
                const doc = frame?.contentDocument;

                if (! frameWindow || ! doc) {
                    return null;
                }

                return {
                    x: frameWindow.scrollX ?? doc.documentElement?.scrollLeft ?? doc.body?.scrollLeft ?? 0,
                    y: frameWindow.scrollY ?? doc.documentElement?.scrollTop ?? doc.body?.scrollTop ?? 0,
                };
            } catch (error) {
                return null;
            }
        },
        capturePreviewScroll() {
            this.pendingPreviewScroll = this.previewScrollPosition();
        },
        scrollPreviewBlockIntoView(blockId = this.selectedBlockId) {
            const targetBlockId = blockId ? Number(blockId) : null;

            if (! targetBlockId) {
                return false;
            }

            this.postToPreview({
                type: 'pilot-preview-scroll-to-block',
                blockId: targetBlockId,
            });

            try {
                const doc = this.$refs.previewFrame?.contentDocument;
                const block = doc?.querySelector(`[data-pilot-editable=block][data-pilot-block-id='${targetBlockId}']`);

                if (! block) {
                    return false;
                }

                block.scrollIntoView({ block: 'center', inline: 'nearest' });

                return true;
            } catch (error) {
                return false;
            }
        },
        restorePreviewScroll() {
            const blockId = this.pendingPreviewScrollBlockId;

            this.pendingPreviewScrollBlockId = null;

            if (this.scrollPreviewBlockIntoView(blockId)) {
                this.pendingPreviewScroll = null;

                return;
            }

            const position = this.pendingPreviewScroll;

            if (! position) {
                return;
            }

            this.pendingPreviewScroll = null;

            try {
                this.$refs.previewFrame?.contentWindow?.scrollTo(position.x, position.y);
            } catch (error) {
                // Cross-origin preview targets cannot be scrolled directly.
            }
        },
        saveLabel() {
            if (this.conflictMessage) {
                return 'Changed elsewhere';
            }

            if (this.saveState === 'saving') {
                return 'Saving...';
            }

            return this.savedJustNow ? 'Saved just now' : 'Saved';
        },
        init() {
            $wire.on('saved', () => {
                this.savedJustNow = true;
                setTimeout(() => { this.savedJustNow = false; }, 2000);
            });

            $wire.on('preview-frame-refresh', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;

                this.refreshPreviewFrame(payload?.url);
            });

            $wire.on('preview-selection-sync', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;

                this.selectedBlockId = payload?.blockId ? Number(payload.blockId) : null;
                this.syncPreviewSelection();
                this.scrollPreviewBlockIntoView();
            });

            this.$watch('selectedBlockId', () => {
                this.$nextTick(() => {
                    this.syncPreviewSelection();
                    this.scrollPreviewBlockIntoView();
                });
            });

            this.$nextTick(() => {
                this.syncPreviewSelection();
                this.scrollPreviewBlockIntoView();
            });

            window.addEventListener('message', (event) => {
                const allowedOrigins = [window.location.origin, ...this.previewTargetOrigins];

                if (! allowedOrigins.includes(event.origin)) {
                    return;
                }

                if (event.data?.type === 'pilot-preview-ready') {
                    this.syncPreviewSelection();
                    this.scrollPreviewBlockIntoView();
                }

                if (event.data?.type === 'pilot-preview-select-block' && event.data?.blockId) {
                    this.selectedBlockId = Number(event.data.blockId);
                    this.syncPreviewSelection();
                    $wire.call('setSelectedBlockFromPreview', Number(event.data.blockId));
                }

                if (event.data?.type === 'pilot-preview-block-action' && event.data?.blockId && event.data?.action) {
                    this.selectedBlockId = Number(event.data.blockId);
                    this.syncPreviewSelection();

                    const actions = {
                        'move-up': 'moveBlockUp',
                        'move-down': 'moveBlockDown',
                        duplicate: 'duplicateBlock',
                        delete: 'deleteBlock',
                    };

                    if (actions[event.data.action]) {
                        if (event.data.action === 'delete' && ! confirm('Delete this block?')) {
                            return;
                        }

                        $wire.call(actions[event.data.action], Number(event.data.blockId));
                    }
                }

                if (event.data?.type === 'pilot-in-context-field-updated' && event.data?.blockId && event.data?.fieldKey) {
                    $wire.call(
                        'updateBlock',
                        Number(event.data.blockId),
                        event.data.fieldKey,
                        event.data.value ?? ''
                    );
                }
            });

            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'b') {
                    e.preventDefault();
                    this.blockLibraryOpen = true;
                }

                if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                    e.preventDefault();
                    $wire.call('saveCheckpoint');
                }
            });
        }
    }"
    class="cms-shell h-screen w-full relative overflow-hidden selection:bg-accent-subtle selection:text-accent-text"
>
    <livewire:admin.content.content-sync-poller
        :content-id="$content->id"
        :key="'content-sync-poller-' . $content->id"
    />

    {{-- Fixed header: top 0, left 70px (after nav), right 500px (before aside when open) --}}
    <header class="fixed top-0 flex h-topbar items-center justify-between gap-4 border-b border-subtle bg-app px-[var(--pad-view)] z-30 transition-[right]" aria-label="Editor toolbar" style="left: var(--admin-nav-width); right: 0;">
        <div class="flex items-center gap-3">
            <nav class="flex items-center text-sm text-tertiary" aria-label="Breadcrumb">
                <a href="{{ route('admin.content.index') }}" class="cursor-pointer transition-colors hover:text-secondary" wire:navigate>{{ $content->space?->name ?? 'Space' }}</a>
                <i class="ph ph-caret-right mx-2 text-xs text-disabled" aria-hidden="true"></i>
                @foreach($this->breadcrumbs as $crumb)
                    <a href="{{ route('admin.content.index', ['folder' => $crumb->id]) }}" wire:navigate class="truncate max-w-[100px] transition-colors hover:text-secondary">{{ $crumb->name }}</a>
                    <i class="ph ph-caret-right mx-2 text-xs text-disabled" aria-hidden="true"></i>
                @endforeach
                <span class="rounded-sm bg-active px-2 py-1 font-semibold text-primary">{{ $content->name }}</span>
            </nav>
            <div class="h-4 w-px bg-strong mx-1" aria-hidden="true"></div>
            @if($content->status === 'draft')
                <div class="cms-badge cms-badge-warning">
                    <div class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></div>
                    <span class="text-xs font-semibold">Unpublished Changes</span>
                </div>
            @else
                <div class="cms-badge cms-badge-success">
                    <div class="w-1.5 h-1.5 rounded-full bg-current"></div>
                    <span class="text-xs font-semibold">Published</span>
                </div>
            @endif
        </div>

        <div class="cms-seg absolute left-1/2 -translate-x-1/2">
            <button type="button" x-on:click="previewDevice = 'desktop'" x-bind:data-active="previewDevice === 'desktop' ? 'true' : 'false'" class="cms-seg-btn !w-9 !px-0" title="Desktop"><i class="ph ph-desktop text-lg"></i></button>
            <button type="button" x-on:click="previewDevice = 'tablet'" x-bind:data-active="previewDevice === 'tablet' ? 'true' : 'false'" class="cms-seg-btn !w-9 !px-0" title="Tablet"><i class="ph ph-device-tablet text-lg"></i></button>
            <button type="button" x-on:click="previewDevice = 'mobile'" x-bind:data-active="previewDevice === 'mobile' ? 'true' : 'false'" class="cms-seg-btn !w-9 !px-0" title="Mobile"><i class="ph ph-device-mobile text-lg"></i></button>
        </div>

        <div class="flex items-center gap-3">
            <div class="text-xs font-medium flex items-center gap-1.5 mr-2" x-bind:class="conflictMessage ? 'text-amber-600' : saveState === 'saving' ? 'text-slate-500' : 'text-slate-400'">
                <i class="ph" x-bind:class="conflictMessage ? 'ph-warning-circle' : saveState === 'saving' ? 'ph-spinner-gap animate-spin' : 'ph-check-circle'"></i>
                <span x-text="saveLabel()">Saved</span>
            </div>
            @if($this->publishedRevisionComparison)
                <button type="button" wire:click="selectPublishedRevision" class="hidden items-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold shadow-sm transition-colors 2xl:inline-flex {{ $this->publishedRevisionComparison['has_changes'] ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100' }}">
                    <i class="ph {{ $this->publishedRevisionComparison['has_changes'] ? 'ph-git-diff' : 'ph-check-circle' }}"></i>
                    @if($this->publishedRevisionComparison['has_changes'])
                        Since publish: {{ count($this->publishedRevisionComparison['content_changes']) }} fields, {{ count($this->publishedRevisionComparison['block_changes']) }} blocks
                    @else
                        Matches published
                    @endif
                </button>
            @endif
            <button type="button" wire:click="undoLastChange" @disabled(! $this->undoRevision) class="cms-iconbtn border border-default bg-card shadow-xs disabled:cursor-not-allowed disabled:opacity-45" title="{{ $this->undoRevision ? 'Undo last change' : 'Nothing to undo' }}" aria-label="Undo last change">
                <i class="ph ph-arrow-counter-clockwise text-lg"></i>
            </button>
            <button type="button" wire:click="openRevisionModal" class="cms-iconbtn border border-default bg-card shadow-xs" title="Revisions" aria-label="Revisions">
                <i class="ph ph-stack text-lg"></i>
            </button>
            <div class="min-w-[92px]">
                <button
                    type="button"
                    wire:click="openCheckpointModal"
                    x-cloak
                    x-show="saveState !== 'saved'"
                    x-bind:disabled="saveState === 'saving'"
                    class="cms-btn cms-btn-primary h-9 w-full disabled:cursor-wait disabled:opacity-75"
                    title="Save checkpoint"
                >
                    Save
                </button>
                <button
                    type="button"
                    wire:click="publish"
                    x-show="saveState === 'saved'"
                    class="cms-btn cms-btn-primary h-9 w-full"
                >
                    Publish
                </button>
            </div>
        </div>
    </header>

    {{-- Content area: below fixed header (pt-16), with right padding when aside is open --}}
    <div class="flex pt-16 h-screen min-w-0" style="margin-right: {{ $drawerOpen ? 'var(--admin-rail-width)' : '3rem' }};">
    {{-- Left: Content Tree (Variant: w-72) --}}
    <aside class="{{ $leftSidebarCollapsed ? 'w-12' : 'w-72' }} bg-app border-r border-subtle flex flex-col shrink-0 z-40 hidden xl:flex" aria-label="Content tree">
        <div class="shrink-0 border-b border-subtle bg-app px-4 py-2 flex items-center {{ $leftSidebarCollapsed ? 'justify-center' : 'justify-between' }}">
            @if($leftSidebarCollapsed)
                <button type="button" wire:click="toggleLeftSidebar" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-500 hover:text-teal-600 transition-colors" title="Expand content panel" aria-label="Expand content panel"><i class="ph ph-sidebar-simple"></i></button>
            @else
            <div class="flex items-center gap-2">
                <span class="font-semibold text-primary">Content</span>
            </div>
            <div class="flex items-center gap-1">
                <a href="{{ route('admin.content.create', ['type' => 'page', 'parent_id' => $content->parent_id ?? null]) }}" wire:navigate class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-500 hover:text-teal-600 transition-colors" title="New page"><i class="ph ph-plus-circle text-xl"></i></a>
                <button type="button" wire:click="toggleLeftSidebar" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-500 hover:text-teal-600 transition-colors" title="Collapse content panel" aria-label="Collapse content panel"><i class="ph ph-sidebar-simple"></i></button>
            </div>
            @endif
        </div>
        @if(!$leftSidebarCollapsed)
        <div class="p-4 shrink-0">
            <div class="relative group">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-teal-500 transition-colors pointer-events-none"></i>
                <input type="text" placeholder="Search content..." class="w-full bg-slate-50 text-sm py-2 pl-9 pr-4 rounded-md border border-slate-200 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none transition-all placeholder-slate-400" />
            </div>
        </div>
        <div class="flex-1 overflow-y-auto px-3 pb-4 space-y-0.5 min-h-0">
            @foreach($this->contentTree as $item)
                @if($item->isFolder())
                    <div class="mb-1">
                        <a href="{{ route('admin.content.index', ['folder' => $item->id]) }}" wire:navigate class="w-full flex items-center gap-2 px-3 py-2 text-sm text-slate-500 hover:bg-slate-50 rounded-md group">
                            <i class="ph ph-caret-down text-xs"></i>
                            <i class="ph ph-globe text-slate-400 group-hover:text-teal-500"></i>
                            <span class="font-medium">{{ $item->name }}</span>
                        </a>
                        <div class="ml-4 pl-3 border-l border-slate-100 space-y-0.5 mt-1">
                            @foreach($item->children ?? [] as $child)
                                <a href="{{ route('admin.content.editor', $child) }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-md cursor-pointer transition {{ $child->id === $content->id ? 'bg-teal-50 text-teal-700 border border-teal-100 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 group' }}">
                                    <div class="flex items-center gap-2">
                                        <i class="ph {{ $child->id === $content->id ? 'ph-fill ph-house' : 'ph-article text-slate-400 group-hover:text-slate-600' }}"></i>
                                        <span class="text-sm {{ $child->id === $content->id ? 'font-semibold' : '' }}">{{ $child->name }}</span>
                                    </div>
                                    @if($child->status === 'published')
                                        <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                                    @else
                                        <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route('admin.content.editor', $item) }}" wire:navigate class="flex items-center justify-between px-3 py-2 rounded-md cursor-pointer transition {{ $item->id === $content->id ? 'bg-teal-50 text-teal-700 border border-teal-100 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 group' }}">
                        <div class="flex items-center gap-2">
                            <i class="ph {{ $item->id === $content->id ? 'ph-fill ph-house' : 'ph-article text-slate-400 group-hover:text-slate-600' }}"></i>
                            <span class="text-sm {{ $item->id === $content->id ? 'font-semibold' : '' }}">{{ $item->name }}</span>
                        </div>
                        @if($item->status === 'published')
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                        @else
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
        <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
            <div class="flex justify-between text-xs mb-1">
                <span class="font-medium text-slate-600">Storage</span>
                <span class="text-slate-500">75%</span>
            </div>
            <div class="h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full bg-teal-500 w-3/4 rounded-full"></div>
            </div>
        </div>
        @endif
    </aside>

    {{-- Center: Canvas only (header is fixed above) --}}
    <main class="flex-1 min-w-0 flex flex-col bg-sunken relative" role="main" aria-label="Page canvas">
        <div class="shrink-0 border-b border-subtle bg-app px-4 py-2">
            <div class="flex items-center justify-between gap-3">
                <div class="cms-seg">
                    <button type="button" x-on:click="canvasMode = 'compose'" x-bind:data-active="canvasMode === 'compose' ? 'true' : 'false'" class="cms-seg-btn">
                        Compose
                    </button>
                    <button type="button" x-on:click="canvasMode = 'preview'" x-bind:data-active="canvasMode === 'preview' ? 'true' : 'false'" class="cms-seg-btn">
                        Preview
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative">
                        <select wire:model.live="selectedPreviewTargetId" class="cms-select min-w-32">
                            <option value="">Internal</option>
                            @foreach($this->previewTargets as $previewTarget)
                                <option value="{{ $previewTarget->id }}">{{ $previewTarget->name }}</option>
                            @endforeach
                        </select>
                        <i class="ph ph-caret-down pointer-events-none absolute right-2.5 top-1.5 text-tertiary"></i>
                    </div>

                    <a href="{{ $this->previewUrl }}" target="_blank" rel="noopener noreferrer" class="cms-btn cms-btn-secondary !h-control-sm">
                        <i class="ph ph-eye" aria-hidden="true"></i>
                        View preview
                    </a>
                </div>
            </div>
        </div>

        <div x-show="canvasMode === 'preview'" x-cloak class="relative flex-1 min-h-0 overflow-hidden bg-sunken p-4">
            <iframe
                x-ref="previewFrame"
                x-on:load="restorePreviewScroll(); syncPreviewSelection()"
                wire:ignore
                name="pilot-cms-preview"
                x-bind:src="previewFrameSrc"
                x-bind:style="`width: ${previewWidth()}`"
                class="mx-auto h-full max-w-full rounded-lg border border-default bg-card shadow-lg transition-[width]"
                title="Live preview"
            ></iframe>

            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="absolute bottom-8 left-1/2 z-50 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 hover:scale-105 transition-transform cursor-pointer border border-slate-700/50 hover:bg-black">
                <i class="ph-bold ph-plus text-teal-400"></i>
                <span class="font-medium text-sm">Add Block</span>
                <div class="w-px h-4 bg-slate-700"></div>
                <span class="text-xs text-slate-400 font-mono">⌘B</span>
            </button>
        </div>

        <div x-show="canvasMode === 'compose'" class="flex min-h-0 flex-1 overflow-hidden">
        <div class="relative flex min-h-0 flex-1 flex-col items-center overflow-hidden bg-slate-100" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
            <div class="w-full flex justify-between items-center px-4 py-2 text-xs text-slate-400 font-mono select-none shrink-0">
                <span x-text="previewDevice === 'desktop' ? '1280px canvas' : previewDevice === 'tablet' ? '768px canvas' : '390px canvas'">1280px canvas</span>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                    <span>Connected</span>
                </div>
            </div>

            <div x-bind:style="`width: ${previewWidth()}`" class="h-full min-h-0 max-w-full overflow-y-auto bg-white pb-20 shadow-2xl ring-1 ring-slate-900/5 transition-[width]">
                <div class="min-h-[500px] p-10 lg:p-14">
                    <h1 class="text-3xl lg:text-4xl font-bold mb-8 text-slate-900">{{ $content->name }}</h1>

                    @if(empty($blocks))
                        <div
                            class="text-center py-24 text-slate-500 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-teal-500 hover:bg-teal-50/30 transition-colors"
                            wire:click="$set('blockLibraryOpen', true)"
                            role="button"
                            tabindex="0"
                        >
                            <i class="ph ph-plus-circle text-6xl mx-auto mb-6 text-slate-300"></i>
                            <p class="font-medium text-lg text-slate-700">No blocks yet</p>
                            <p class="text-sm mt-1">Click to add your first block</p>
                        </div>
                    @else
                        @foreach($blocks as $index => $block)
                            @include('livewire.admin.content.partials.canvas-block', [
                                'block' => $block,
                                'blockTypes' => $blockTypes,
                                'selectedBlockId' => $selectedBlockId,
                                'depth' => 0,
                            ])
                        @endforeach
                        <div class="flex justify-center py-4">
                            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="flex items-center gap-2 px-4 py-2 rounded-lg border-2 border-dashed border-slate-300 text-slate-500 hover:border-teal-500 hover:text-teal-600 transition-colors text-sm">
                                <i class="ph ph-plus"></i>
                                Add block
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Floating Add Block button --}}
            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="absolute bottom-8 z-50 bg-slate-900 text-white px-6 py-3 rounded-full shadow-2xl flex items-center gap-3 hover:scale-105 transition-transform cursor-pointer border border-slate-700/50 hover:bg-black">
                <i class="ph-bold ph-plus text-teal-400"></i>
                <span class="font-medium text-sm">Add Block</span>
                <div class="w-px h-4 bg-slate-700"></div>
                <span class="text-xs text-slate-400 font-mono">⌘B</span>
            </button>
        </div>
        </div>
    </main>
    </div>{{-- /content area (pt-16, pr-[500px] when drawer open) --}}

    {{-- Right: Edit Panel — fixed top 0, bottom 0, right 0, 500px, 100% view height --}}
    @php
        $editPanelTabs = ['content' => 'Content', 'comments' => 'Comments', 'validation' => 'Checks', 'seo' => 'Advanced'];
        $hasSelectedBlock = $selectedBlockId !== null;
        $sel = $hasSelectedBlock ? $this->selectedBlock : null;
        $bt = $sel ? ($blockTypes[$sel['type']] ?? null) : null;
    @endphp
    <aside class="fixed top-16 bottom-0 right-0 bg-white border-l border-slate-200 flex flex-col z-40 {{ $drawerOpen ? 'shadow-xl' : 'shadow-lg' }}" aria-label="Edit panel" style="width: {{ $drawerOpen ? 'var(--admin-rail-width)' : '3rem' }};">
        {{-- Header: breadcrumb nav (Page > Block Name) with actions --}}
        <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-2 flex items-center {{ $drawerOpen ? 'justify-between' : 'justify-center' }}">
            @if($drawerOpen)
            <div class="flex items-center gap-1.5 min-w-0">
                {{-- "Page" is always the root, clickable to deselect block --}}
                <button type="button" wire:click="$set('selectedBlockId', null)" class="flex items-center gap-1.5 {{ $hasSelectedBlock ? 'text-slate-400 hover:text-teal-600' : 'text-slate-800' }} transition-colors shrink-0">
                    <span class="w-6 h-6 rounded bg-teal-50 text-teal-600 flex items-center justify-center text-xs font-bold border border-teal-100">P</span>
                    <span class="text-sm {{ $hasSelectedBlock ? 'font-medium' : 'font-bold' }}">Page</span>
                </button>
                @if($hasSelectedBlock)
                    <i class="ph ph-caret-right text-xs text-slate-300 shrink-0 mx-0.5"></i>
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-600 flex items-center justify-center text-xs font-bold border border-teal-100 shrink-0">{{ $bt ? strtoupper(mb_substr($bt->name, 0, 1)) : 'B' }}</span>
                        <span class="font-bold text-slate-800 text-sm truncate">{{ $bt ? $bt->name : 'Block' }}</span>
                    </div>
                @endif
            </div>
            <div class="flex gap-1 shrink-0">
                @if($hasSelectedBlock)
                <button type="button" wire:click="duplicateBlock({{ $selectedBlockId }})" class="w-7 h-7 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600" title="Duplicate"><i class="ph ph-copy"></i></button>
                <button type="button" wire:click="deleteBlock({{ $selectedBlockId }})" wire:confirm="Delete this block?" class="w-7 h-7 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-red-500" title="Delete"><i class="ph ph-trash"></i></button>
                @endif
                <button type="button" wire:click="toggleDrawer" class="w-7 h-7 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-teal-600" title="Collapse inspector" aria-label="Collapse inspector"><i class="ph ph-sidebar-simple"></i></button>
            </div>
            @else
                <button type="button" wire:click="toggleDrawer" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-500 hover:text-teal-600 transition-colors" title="Expand inspector" aria-label="Expand inspector"><i class="ph ph-sidebar-simple"></i></button>
            @endif
        </div>

        @if($drawerOpen)
        {{-- Tabs --}}
        <div class="flex border-b border-slate-200 bg-slate-50/50 shrink-0">
            @foreach($editPanelTabs as $tab => $label)
            <button type="button" wire:click="$wire.set('rightPanelTab', '{{ $tab }}')" class="flex-1 py-3 text-xs font-medium transition-colors {{ $rightPanelTab === $tab ? 'font-semibold text-teal-600 border-b-2 border-teal-500 bg-white' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-7 min-h-0">

            {{-- CONTENT TAB --}}
            <div class="{{ $rightPanelTab === 'content' ? '' : 'hidden' }}" role="tabpanel">

                @if($hasSelectedBlock && $bt)
                    {{-- When a block is selected: show ONLY the block fields --}}
                    <livewire:admin.content.block-editor
                        :block="$sel"
                        :block-type="$bt"
                        :expanded-repeater-items="$expandedRepeaterItemsByBlock[(int) $selectedBlockId] ?? []"
                        :key="'block-editor-' . $selectedBlockId . '-' . $editorSyncVersion"
                    />
                @else
                    {{-- No block selected: show full page edit form --}}
                    <div class="space-y-7">

                    {{-- Name --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Name</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">text</span>
                        </div>
                        <input type="text" value="{{ $content->name }}" wire:change="updateContent('name', $event.target.value)" placeholder="Page title" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm transition-all" />
                    </div>

                    {{-- Slug --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Slug</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">text</span>
                        </div>
                        <input type="text" value="{{ $content->slug }}" wire:change="updateContent('slug', $event.target.value)" placeholder="page-slug" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm transition-all" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Categories</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">list</span>
                        </div>
                        <input type="text" value="{{ implode(', ', $content->categories ?? []) }}" wire:change="updateTaxonomy('categories', $event.target.value)" placeholder="News, Destinations" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm transition-all" />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated categories for grouping content.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Tags</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">list</span>
                        </div>
                        <input type="text" value="{{ implode(', ', $content->tags ?? []) }}" wire:change="updateTaxonomy('tags', $event.target.value)" placeholder="family travel, hiking, summer" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm transition-all" />
                        <p class="mt-1 text-xs text-slate-400">Comma-separated tags for filtering and discovery.</p>
                    </div>

                    {{-- Parent Folder (pages only) --}}
                    @if($content->isPage())
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Content Type</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">schema</span>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('content_type_id', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="">Generic Page</option>
                                @foreach($this->contentTypes as $contentType)
                                    <option value="{{ $contentType->id }}" {{ $content->content_type_id === $contentType->id ? 'selected' : '' }}>{{ $contentType->name }}</option>
                                @endforeach
                            </select>
                            <i class="ph ph-caret-down absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Parent Folder</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">select</span>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('parent_id', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="">None (Root)</option>
                                @foreach($this->folders as $folder)
                                    <option value="{{ $folder->id }}" {{ $content->parent_id == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                @endforeach
                            </select>
                            <i class="ph ph-caret-down absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    @endif

                    {{-- Status --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Status</label>
                            <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">select</span>
                        </div>
                        <div class="relative">
                            <select wire:change="updateContent('status', $event.target.value)" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm appearance-none cursor-pointer">
                                <option value="draft" {{ $content->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $content->status === 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                            <i class="ph ph-caret-down absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    </div>{{-- /space-y-7 --}}

                    {{-- Blocks list --}}
                    <div class="pt-5 mt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Blocks</span>
                            <button type="button" wire:click="$set('blockLibraryOpen', true)" class="text-[10px] text-teal-600 font-bold hover:underline">+ Add</button>
                        </div>
                        <div wire:sort="sortItem" class="space-y-0.5">
                            @foreach($blocks as $block)
                            <div wire:sort:item="{{ $block['id'] }}" wire:key="block-item-{{ $block['id'] }}" data-block-tree-item="{{ $block['id'] }}" class="flex items-center gap-2 px-3 py-2.5 rounded-md {{ $selectedBlockId === $block['id'] ? 'bg-teal-50 border border-teal-100' : 'hover:bg-slate-50' }}">
                                <span class="cursor-grab active:cursor-grabbing touch-none text-slate-400 hover:text-slate-600" wire:sort:handle aria-label="Drag to reorder"><i class="ph ph-dots-six-vertical"></i></span>
                                <div class="flex-1 min-w-0 py-1 cursor-pointer" wire:click="$set('selectedBlockId', {{ $block['id'] }})">
                                    <span class="font-medium text-sm truncate block text-slate-700">{{ $blockTypes[$block['type']]->name ?? $block['type'] }}</span>
                                </div>
                                <button type="button" wire:click="deleteBlock({{ $block['id'] }})" wire:confirm="Delete this block?" wire:sort:ignore class="w-7 h-7 flex items-center justify-center rounded hover:bg-slate-100 text-slate-400 hover:text-red-500" aria-label="Delete block"><i class="ph ph-trash"></i></button>
                            </div>
                            @endforeach
                        </div>
                        @if(empty($blocks))
                        <p class="py-6 text-center text-sm text-slate-400">No blocks. Click Add to insert one.</p>
                        @endif
                    </div>
                @endif

                <div class="h-10"></div>
            </div>

            {{-- COMMENTS TAB --}}
            <div class="{{ $rightPanelTab === 'comments' ? '' : 'hidden' }} space-y-5" role="tabpanel">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Presence</span>
                    <div wire:poll.15000ms="touchPresence" class="space-y-2">
                        @forelse($this->activePresences as $presence)
                            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-500 text-xs font-bold text-white">
                                    {{ $presence->user?->initials() ?? '?' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-semibold text-slate-700">{{ $presence->user?->name ?? 'Collaborator' }}</div>
                                    <div class="truncate text-xs text-slate-400">
                                        {{ $presence->status === 'editing' ? 'Editing' : 'Viewing' }}
                                        @if($presence->selectedBlock)
                                            {{ $presence->selectedBlock->reusable_name ?? $presence->selectedBlock->type }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No other editors are active.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Block comments</span>
                    @if($hasSelectedBlock)
                        <div class="space-y-3">
                            @forelse($this->selectedBlockComments as $comment)
                                <div class="rounded-lg border border-slate-200 bg-white p-3">
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <span class="text-xs font-semibold text-slate-600">{{ $comment->user?->name ?? 'System' }}</span>
                                        <button type="button" wire:click="resolveBlockComment({{ $comment->id }})" class="text-xs font-semibold text-teal-600 hover:underline">Resolve</button>
                                    </div>
                                    <p class="text-sm text-slate-600">{{ $comment->body }}</p>
                                </div>
                            @empty
                                <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No open comments on this block.</p>
                            @endforelse
                            <textarea rows="3" wire:model="newCommentBody" placeholder="Leave a comment for reviewers" class="w-full resize-none rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-sm outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500"></textarea>
                            @error('newCommentBody') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="button" wire:click="addBlockComment" class="w-full rounded-md bg-teal-500 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-600">Add comment</button>
                        </div>
                    @else
                        <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">Select a block to view or add comments.</p>
                    @endif
                </div>
            </div>

            {{-- VALIDATION TAB --}}
            <div class="{{ $rightPanelTab === 'validation' ? '' : 'hidden' }} space-y-5" role="tabpanel">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Validation panel</span>
                    <div class="space-y-2">
                        @forelse($this->validationIssues as $issue)
                            <button
                                type="button"
                                @if($issue['block_id']) wire:click="setSelectedBlockFromPreview({{ $issue['block_id'] }})" @endif
                                class="flex w-full items-start gap-3 rounded-lg border {{ $issue['severity'] === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-amber-200 bg-amber-50 text-amber-700' }} p-3 text-left"
                            >
                                <i class="ph {{ $issue['severity'] === 'error' ? 'ph-warning-octagon' : 'ph-warning-circle' }} mt-0.5"></i>
                                <span class="text-sm font-medium">{{ $issue['label'] }}</span>
                            </button>
                        @empty
                            <div class="rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm font-medium text-teal-700">No validation issues found.</div>
                        @endforelse
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Reusable blocks</span>
                    @if($hasSelectedBlock)
                        <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <input type="text" wire:model="reusableBlockName" placeholder="Reusable block name" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500" />
                            @error('reusableBlockName') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="button" wire:click="makeSelectedBlockReusable" class="w-full rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 hover:bg-teal-100">Save selected as reusable</button>
                        </div>
                    @endif
                    <div class="mt-3 space-y-2">
                        @forelse($this->reusableBlocks as $reusableBlock)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-700">{{ $reusableBlock->reusable_name }}</div>
                                    <div class="truncate text-xs text-slate-400">{{ $reusableBlock->type }} · {{ $reusableBlock->content?->name }}</div>
                                </div>
                                <button type="button" wire:click="insertReusableBlock({{ $reusableBlock->id }})" class="shrink-0 rounded-md px-2 py-1 text-xs font-semibold text-teal-600 hover:bg-teal-50">Insert</button>
                            </div>
                        @empty
                            <p class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">No reusable blocks yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ADVANCED TAB (SEO + Status + History) --}}
            <div class="{{ $rightPanelTab === 'seo' ? '' : 'hidden' }} space-y-6" role="tabpanel">
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">SEO</span>
                    <div class="group mb-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Meta title</label>
                        <input type="text" value="{{ $content->meta['meta_title'] ?? '' }}" wire:change="updateContentMeta('meta_title', $event.target.value)" placeholder="Page title for search engines" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm" />
                    </div>
                    <div class="group">
                        <label class="text-xs text-slate-600 block mb-1.5">Meta description</label>
                        <textarea rows="3" wire:change="updateContentMeta('meta_description', $event.target.value)" placeholder="Brief description" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none resize-none">{{ $content->meta['meta_description'] ?? '' }}</textarea>
                    </div>
                    <div class="group mt-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Canonical URL</label>
                        <input type="text" value="{{ $content->meta['canonical_url'] ?? '' }}" wire:change="updateContentMeta('canonical_url', $event.target.value)" placeholder="https://example.com/page" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm" />
                    </div>
                    <div class="group mt-4">
                        <label class="text-xs text-slate-600 block mb-1.5">Open Graph image</label>
                        <input type="text" value="{{ $content->meta['og_image'] ?? '' }}" wire:change="updateContentMeta('og_image', $event.target.value)" placeholder="/storage/social-card.jpg" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm" />
                    </div>
                    <label class="mt-4 flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:change="updateContentMeta('noindex', $event.target.checked)" {{ ! empty($content->meta['noindex']) ? 'checked' : '' }} class="rounded border-slate-300 text-teal-500 focus:ring-teal-500" />
                        Hide from search engines
                    </label>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wide block mb-2">Workflow</span>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">State</span>
                            <span class="rounded bg-white px-2 py-0.5 text-xs text-slate-600">{{ str($content->workflow_status)->replace('_', ' ')->title() }}</span>
                        </div>
                        @if($content->scheduled_for)
                            <div class="mt-2 text-xs text-slate-500">Scheduled for {{ $content->scheduled_for->format('M j, Y g:i A') }}</div>
                        @endif
                        @if($content->reviewer)
                            <div class="mt-2 text-xs text-slate-500">Reviewer: {{ $content->reviewer->name }}</div>
                        @endif
                        @if($content->review_due_at)
                            <div class="mt-1 text-xs text-slate-500">Due {{ $content->review_due_at->format('M j, Y g:i A') }}</div>
                        @endif
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <button type="button" wire:click="requestReview" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">Request Review</button>
                            <button type="button" wire:click="unpublish" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">Unpublish</button>
                            <button type="button" wire:click="approveReview" class="rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 hover:bg-teal-100">Approve</button>
                            <button type="button" wire:click="requestChanges" class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Request changes</button>
                        </div>
                    </div>
                    <div class="mt-3 space-y-2 rounded-lg border border-slate-200 bg-white p-3">
                        <label class="text-xs text-slate-600 block">Assign reviewer</label>
                        <select wire:model="reviewerId" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500">
                            <option value="">Unassigned</option>
                            @foreach($this->reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>
                            @endforeach
                        </select>
                        <label class="text-xs text-slate-600 block">Review due date</label>
                        <input type="datetime-local" wire:model="reviewDueAt" class="w-full rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500" />
                        <label class="text-xs text-slate-600 block">Review note</label>
                        <textarea rows="3" wire:model="reviewNote" class="w-full resize-none rounded-lg border border-slate-200 bg-white p-2.5 text-sm text-slate-700 outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500"></textarea>
                        <button type="button" wire:click="assignReview" class="w-full rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Assign review</button>
                    </div>
                    <div class="mt-3 space-y-2">
                        <label class="text-xs text-slate-600 block">Schedule publish</label>
                        <input type="datetime-local" wire:model="scheduledFor" class="w-full p-2.5 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none shadow-sm" />
                        <button type="button" wire:click="schedulePublishing" class="w-full rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 hover:bg-teal-100">Schedule</button>
                        @error('scheduledFor') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer: Block ID + Type when block selected, otherwise Last updated --}}
        <div class="p-4 border-t border-slate-200 bg-slate-50 shrink-0">
            <div class="flex flex-col gap-1">
                @if($hasSelectedBlock)
                    <div class="flex justify-between items-center text-[10px] font-mono text-slate-400">
                        <span>Block ID</span>
                        <span>{{ Str::limit($selectedBlockId, 8) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-mono text-slate-400">
                        <span>Type</span>
                        <span>{{ $sel['type'] ?? '—' }}</span>
                    </div>
                @else
                    <div class="flex justify-between items-center text-xs text-slate-500">
                        <span>Last updated</span>
                        <span x-text="savedJustNow ? 'Just now' : '{{ $lastSavedAt ? $lastSavedAt->diffForHumans() : '2 mins ago' }}'">{{ $lastSavedAt ? $lastSavedAt->diffForHumans() : '2 mins ago' }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </aside>

    @if($revisionModalOpen)
        <div
            wire:keydown.escape="closeRevisionModal"
            class="fixed inset-0 z-50"
            role="dialog"
            aria-modal="true"
            aria-labelledby="revision-modal-title"
        >
            <button type="button" wire:click="closeRevisionModal" class="absolute inset-0 h-full w-full bg-slate-500/25" aria-label="Close revisions"></button>
            <div class="relative flex h-full w-full items-center justify-center p-4 sm:p-6">
                <div class="relative flex h-[calc(100vh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl ring-1 ring-slate-900/5 sm:h-[calc(100vh-3rem)]">
                    <button type="button" wire:click="closeRevisionModal" class="absolute right-3 top-3 z-[60] flex h-9 w-9 items-center justify-center rounded-md bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/10 hover:bg-slate-50 hover:text-slate-700" title="Close" aria-label="Close revisions">
                        <i class="ph ph-x text-lg"></i>
                    </button>
                    <div class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 pr-16">
                        <div>
                            <h2 id="revision-modal-title" class="text-base font-semibold text-slate-800">Revisions</h2>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $this->revisions->count() }} of {{ $this->revisionTotalCount }} loaded</p>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4">
                        @include('livewire.admin.content.partials.revisions-panel')
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Block Library Modal --}}
    <div
        x-cloak
        x-show="blockLibraryOpen"
        x-on:keydown.escape.window="blockLibraryOpen = false"
        x-on:click.self="blockLibraryOpen = false"
        class="fixed inset-0 z-50"
    >
        <div class="absolute inset-0 bg-slate-500/25" aria-hidden="true"></div>
        <div class="relative h-full w-full overflow-y-auto p-4 sm:p-6 md:p-20">
            <div
                x-on:click.stop
                x-data="{
                    blockSearch: '',
                    blockMatches(name, key, description) {
                        const query = this.blockSearch.trim().toLowerCase();

                        if (! query) {
                            return true;
                        }

                        return [name, key, description].some((value) => String(value || '').toLowerCase().includes(query));
                    },
                    hasBlockMatches() {
                        return Array.from(this.$refs.blockGrid?.querySelectorAll('[data-block-search-text]') || []).some((element) => {
                            return ! this.blockSearch.trim() || element.dataset.blockSearchText.includes(this.blockSearch.trim().toLowerCase());
                        });
                    },
                }"
                x-effect="if (! blockLibraryOpen) blockSearch = ''"
                class="mx-auto max-w-lg overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-slate-900/5"
            >
                <div class="p-6">
                    <h2 class="mb-1 text-lg font-bold text-slate-800">Add Block</h2>
                    @if($addBlockPosition === 'inside' && $addBlockParentId)
                        <p class="mb-4 text-sm text-slate-500">Choose a block to nest inside the selected container.</p>
                    @else
                        <p class="mb-4 text-sm text-slate-500">Choose a block to add to this page.</p>
                    @endif
                    @if($blockTypes->isEmpty())
                    <p class="text-sm text-slate-500">You don't have any block types yet. Create one to start building pages.</p>
                    <div class="mt-4">
                        <a href="{{ route('admin.blocks.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-md bg-teal-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-teal-600">Create Block Type</a>
                    </div>
                    @else
                    <div class="relative mb-4">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input
                            type="search"
                            x-model.debounce.100ms="blockSearch"
                            placeholder="Search blocks"
                            class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition-all placeholder:text-slate-400 focus:border-teal-500 focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                    <div x-ref="blockGrid" class="grid grid-cols-2 gap-3">
                        @foreach($blockTypes as $blockType)
                        @php
                            $blockDescription = $blockType->schema['description'] ?? '';
                            $blockSearchText = strtolower(trim($blockType->name.' '.$blockType->key.' '.$blockDescription));
                        @endphp
                        <button
                            type="button"
                            x-show="blockMatches(@js($blockType->name), @js($blockType->key), @js($blockDescription))"
                            wire:click="addBlock('{{ $blockType->key }}')"
                            data-block-search-text="{{ $blockSearchText }}"
                            class="rounded-lg border border-slate-200 p-4 text-left transition-colors hover:bg-slate-50 focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                        >
                            <div class="font-medium text-slate-800">{{ $blockType->name }}</div>
                            <div class="mt-0.5 text-sm text-slate-500">{{ $blockDescription }}</div>
                        </button>
                        @endforeach
                    </div>
                    <div x-show="! hasBlockMatches()" x-cloak class="rounded-lg border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                        No blocks match your search.
                    </div>
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <a href="{{ route('admin.blocks.index') }}" class="text-sm text-teal-600 hover:underline" wire:navigate>Manage block types</a>
                    </div>
                    @endif
                    <div class="mt-6 flex justify-end">
                        <button type="button" x-on:click="blockLibraryOpen = false" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewire('admin.assets.asset-picker-modal')
</div>
