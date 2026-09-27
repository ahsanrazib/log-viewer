# SolverCircle Log Viewer

A modern, interactive, real-time log viewer for Laravel applications. Easily inspect, filter, search, download, and manage Monolog log files directly from your application's browser UI.

![Laravel Log Viewer](https://raw.githubusercontent.com/laravel/art/master/logo-laravel-readme.min.svg)

## Features

- 📁 **Multi-File Log Management**: Automatically detects log files in `storage/logs` (such as `laravel.log` or daily rotated log files like `laravel-2026-09-27.log`).
- 🎨 **Responsive UI with Dark/Light Mode**: Self-contained Blade template using Tailwind CSS and Alpine.js. Works out-of-the-box without requiring frontend dependencies in the host application.
- 🏷️ **Level Filter & Statistics**: Live overview counts and filtering for `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `NOTICE`, `INFO`, and `DEBUG` entries.
- 🔍 **Real-time Search**: Search log entry messages, formatted context data, or stack traces instantly.
- ⚡ **Live Polling / Auto-refresh**: Configurable real-time polling (3s, 5s, 10s intervals) for live log monitoring.
- 🔍 **Expandable Stack Traces & Context**: Formatted JSON context inspector and stack trace reader.
- ⚡ **Management Actions**:
  - Download raw `.log` file.
  - Clear log file content.
  - Delete log file.
- 🔒 **Security & Customization**: Configurable route prefix, route domain, custom authorization middleware (e.g. `auth`, `can:viewLogs`), and security checks against path traversal.

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

## Usage

Access the Log Viewer in your browser at:

```
http://your-app.test/log-viewer
```

### Configuration (`config/log-viewer.php`)

```php
return [
    // Toggle Log Viewer on/off
    'enabled' => env('LOG_VIEWER_ENABLED', true),

    // Route prefix (e.g., /log-viewer or /admin/logs)
    'route_prefix' => env('LOG_VIEWER_ROUTE_PREFIX', 'log-viewer'),

    // Protecting the Log Viewer with Middleware
    'middleware' => [
        'web',
        'auth', // Optional: add auth or custom gate middleware
    ],

    // Path where log files are stored
    'storage_path' => storage_path('logs'),

    // Log items per page
    'per_page' => 50,

    // Default theme ('auto', 'dark', 'light')
    'theme' => 'auto',
];
```

### Programmatic Usage & Facade

```php
use SolverCircle\LogViewer\Facades\LogViewer;

// List all log files
$files = LogViewer::getFiles();

// Fetch parsed logs with filtering
$logs = LogViewer::getLogs(fileName: 'laravel.log', level: 'ERROR', query: 'database');

// Clear log file content
LogViewer::clearLogFile('laravel.log');

// Delete log file
LogViewer::deleteLogFile('laravel-2026-09-26.log');
```

---

## Testing

Run tests via Pest:

```bash
vendor/bin/pest packages/log-viewer/tests/Feature/LogViewerTest.php
```

---

## License

The MIT License (MIT).
