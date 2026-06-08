@if(config('cms.editor_bridge.enabled', true))
    <script>
        (() => {
            const liveRootSelector = '{{ config('cms.editor_bridge.live_root', '[data-pilot-live-root]') }}';
            const endpoint = '{{ route('api.preview.render') }}';

            window.PilotCms = window.PilotCms || {};
            window.PilotCms.livePreview = {
                async render(payload, options = {}) {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            ...payload,
                            theme: options.theme || '{{ $theme ?? config('cms.theme', 'default') }}',
                            locale: options.locale || document.documentElement.lang || '{{ app()->getLocale() }}',
                        }),
                    });

                    if (! response.ok) {
                        throw new Error(`Pilot live preview failed with ${response.status}`);
                    }

                    const result = await response.json();
                    const liveRoot = document.querySelector(options.liveRootSelector || liveRootSelector);

                    if (liveRoot && result.html) {
                        liveRoot.innerHTML = result.html;
                    }

                    return result;
                },
            };

            document.addEventListener('click', (event) => {
                const editable = event.target.closest('[data-pilot-editable="block"]');

                if (! editable || window.parent === window) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                window.parent.postMessage({
                    type: 'pilot-preview-select-block',
                    blockId: Number(editable.dataset.pilotBlockId),
                    component: editable.dataset.pilotComponent,
                    componentPath: editable.dataset.pilotComponentPath,
                }, window.location.origin);
            });
        })();
    </script>
@endif
