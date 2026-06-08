<?php

return [
    'theme' => env('CMS_THEME', 'default'),
    'default_space' => env('CMS_DEFAULT_SPACE'),
    'home_slug' => env('CMS_HOME_SLUG', 'home'),
    'delivery_source' => env('CMS_DELIVERY_SOURCE', 'mysql'),
    'editor_bridge' => [
        'enabled' => env('CMS_EDITOR_BRIDGE_ENABLED', true),
        'live_preview' => env('CMS_LIVE_PREVIEW_ENABLED', true),
        'live_root' => env('CMS_LIVE_PREVIEW_ROOT', '[data-pilot-live-root]'),
    ],
    'frontend_editor' => [
        'enabled' => env('CMS_FRONTEND_EDITOR_ENABLED', true),
    ],
];
