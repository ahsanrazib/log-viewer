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
    | Email Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Periodic email digest alerts for recent logs.
    | Defaults:
    | - Enabled: false
    | - Email Address: null (LOG_VIEWER_EMAIL_TO)
    | - Time Interval: 30 minutes (LOG_VIEWER_EMAIL_INTERVAL)
    | - Log Types: ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'] (LOG_VIEWER_EMAIL_LEVELS)
    | Note: Email notifications only run when app environment is 'production'.
    |
    */
    'email_notifications' => [
        'enabled' => (bool) env('LOG_VIEWER_EMAIL_ENABLED', false),
        'to' => env('LOG_VIEWER_EMAIL_TO', null),
        'interval_minutes' => (int) env('LOG_VIEWER_EMAIL_INTERVAL', 30),
        'levels' => array_map('trim', explode(',', env('LOG_VIEWER_EMAIL_LEVELS', 'ERROR,CRITICAL,ALERT,EMERGENCY'))),
    ],

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
