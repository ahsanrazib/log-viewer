# SolverCircle Log Viewer

A modern, interactive, real-time log viewer for Laravel applications. Easily inspect, filter, search, download, and manage Monolog log files directly from your application's browser UI.

![Laravel Log Viewer](https://raw.githubusercontent.com/laravel/art/master/logo-laravel-readme.min.svg)

## Features

- 📁 **Multi-File Log Management**: Automatically detects log files in `storage/logs` (such as `laravel.log` or daily rotated log files like `laravel-2026-09-27.log`).
- 🎨 **Responsive UI with Dark/Light Mode**: Self-contained Blade template using Tailwind CSS and Alpine.js. Works out-of-the-box without requiring frontend dependencies in the host application.
- 🏷️ **Level Filter & Statistics**: Live overview counts and filtering for `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `NOTICE`, `INFO`, and `DEBUG` entries.
- 🔍 **Real-time Search**: Search log entry messages, formatted context data, or stack traces instantly.
- ⚡ **Live Polling / Auto-refresh**: Configurable real-time polling (3s, 5s, 10s intervals) for live log monitoring.
- 📬 **Email Digest Notifications**: Periodic email alerts of error logs in production (e.g. logs from the last 30 minutes).
- 🔑 **Passkey Protection**: Restrict log viewer access with a configurable secret passkey.
- ⚡ **Management Actions**:
  - Copy log entry to clipboard with 1-click button.
  - Download raw `.log` file.
  - Clear log file content.
  - Delete log file.

---

## Installation

You can install the package via composer:

```bash
composer require solvercircle/log-viewer
```

Publish the package configuration file and views (optional):

```bash
php artisan vendor:publish --provider="SolverCircle\LogViewer\LogViewerServiceProvider"
```

---

## Configuration (`config/log-viewer.php`)

```php
return [
    'enabled' => env('LOG_VIEWER_ENABLED', true),
    'passkey' => env('LOG_VIEWER_PASSKEY', null),
    'route_prefix' => env('LOG_VIEWER_ROUTE_PREFIX', 'log-viewer'),

    // Email Digest Notifications (production only)
    'email_notifications' => [
        'enabled' => (bool) env('LOG_VIEWER_EMAIL_ENABLED', false),
        'to' => env('LOG_VIEWER_EMAIL_TO', null),
        'interval_minutes' => (int) env('LOG_VIEWER_EMAIL_INTERVAL', 30),
        'levels' => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
    ],

    'storage_path' => storage_path('logs'),
    'per_page' => 50,
    'theme' => 'auto',
];
```

### Environment Variables

```env
LOG_VIEWER_ENABLED=true
LOG_VIEWER_PASSKEY=your-secret-passkey
LOG_VIEWER_EMAIL_ENABLED=true
LOG_VIEWER_EMAIL_TO=admin@example.com
LOG_VIEWER_EMAIL_INTERVAL=30
LOG_VIEWER_EMAIL_LEVELS=ERROR,CRITICAL,ALERT,EMERGENCY
```

---

## Email Digest Notifications

The email notification feature parses logs that occurred in the last specified time interval (default 30 minutes) and sends an HTML summary email to configured recipients.

> **Note:** Email notifications only execute when `APP_ENV=production` (you can use `--force` to test locally).

### Schedule Command

To run periodic email checks, schedule the command in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('log-viewer:send-email-digest')->everyThirtyMinutes();
```

---

## Testing

Run tests via Pest:

```bash
vendor/bin/pest packages/log-viewer/tests/Feature
```

---

## License

The MIT License (MIT).
