{!! view()->file(base_path('vendor/pilot/laravel/resources/views/editor-bridge.blade.php'))->render() !!}

@if(config('pilot.editor_bridge.enabled', true))
    <script>
        (() => {
            const parentOrigin = (() => {
                try {
                    return document.referrer ? new URL(document.referrer).origin : '*';
                } catch (error) {
                    return '*';
                }
            })();

            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'pilot-preview-ready',
                }, parentOrigin);
            }
        })();
    </script>
@endif
