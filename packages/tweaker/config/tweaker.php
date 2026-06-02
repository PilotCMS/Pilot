<?php

return [
    'enabled' => env('TWEAKER_ENABLED', false),

    'route_prefix' => env('TWEAKER_ROUTE_PREFIX', '_tweaker'),
    'route_middleware' => ['web'],

    'allowed_paths' => [
        'resources',
        'storage/app/tweaker',
    ],

    'default_less_path' => env('TWEAKER_LESS_PATH', 'resources/less/tweaker-overrides.less'),
    'default_blade_path' => env('TWEAKER_BLADE_PATH', 'resources/views/tweaker/overrides.blade.php'),

    'log_to_database' => env('TWEAKER_LOG_DB', false),

    'allowed_models' => ['*'],
    'allowed_fields' => ['*'],
];
