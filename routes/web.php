<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use SolverCircle\LogViewer\Http\Controllers\LogViewerController;
use SolverCircle\LogViewer\Http\Middleware\EnsurePasskeyIsAuthorized;

$prefix = Config::get('log-viewer.route_prefix', 'log-viewer');
$domain = Config::get('log-viewer.route_domain');
$middleware = array_merge(
    (array) Config::get('log-viewer.middleware', ['web']),
    [EnsurePasskeyIsAuthorized::class]
);

Route::group([
    'prefix' => $prefix,
    'domain' => $domain,
    'middleware' => $middleware,
    'as' => 'log-viewer.',
], function () {
    Route::get('/', [LogViewerController::class, 'index'])->name('index');
    Route::post('/auth', [LogViewerController::class, 'authenticatePasskey'])->name('auth');
    Route::post('/lock', [LogViewerController::class, 'lockPasskey'])->name('lock');
    Route::get('/api/logs', [LogViewerController::class, 'api'])->name('api');
    Route::get('/download/{file}', [LogViewerController::class, 'download'])->name('download');
    Route::post('/clear/{file}', [LogViewerController::class, 'clear'])->name('clear');
    Route::delete('/delete/{file}', [LogViewerController::class, 'delete'])->name('delete');
});
