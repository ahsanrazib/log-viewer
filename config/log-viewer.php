<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Viewer Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Log Viewer UI route entirely.
    |
    */
    'enabled' => (bool) env('LOG_VIEWER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Passkey Protection
    |--------------------------------------------------------------------------
    |
    | Set a secret passkey to restrict access to the Log Viewer.
    | When set (non-empty string), users must enter this passkey to view or manage logs.
    | Set to null or empty string to disable passkey protection.
    |
    */
    'passkey' => env('LOG_VIEWER_PASSKEY', null),

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route Prefix & Domain
    |--------------------------------------------------------------------------
    |
    | Specify the route prefix and optional domain for the Log Viewer UI.
    |
    */
    'route_prefix' => env('LOG_VIEWER_ROUTE_PREFIX', 'log-viewer'),
    'route_domain' => env('LOG_VIEWER_ROUTE_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Middleware
    |--------------------------------------------------------------------------
    |
    | Specify the middleware array to protect the Log Viewer UI.
    | By default, 'web' middleware is applied. You can add custom middleware or gates.
    |
    */
    'middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Directory
    |--------------------------------------------------------------------------
    |
    | Directory where log files are stored. Defaults to storage_path('logs').
    |
    */
    'storage_path' => storage_path('logs'),

    /*
    |--------------------------------------------------------------------------
    | Log Parsing Configuration
    |--------------------------------------------------------------------------
    |
    | Default entries per page and max safety file size in bytes.
    |
    */
    'per_page' => 50,
    'max_file_size' => 50 * 1024 * 1024, // 50 MB

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | Default theme appearance: 'auto', 'dark', or 'light'.
    |
    */
    'theme' => 'auto',
];
