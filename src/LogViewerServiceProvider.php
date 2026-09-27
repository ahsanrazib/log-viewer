<?php

namespace Ahsanrazib\LogViewer;

use Illuminate\Support\ServiceProvider;
use Ahsanrazib\LogViewer\Console\Commands\SendLogDigestMailCommand;
use Ahsanrazib\LogViewer\Services\LogParserService;
use Ahsanrazib\LogViewer\Services\LogViewerService;

class LogViewerServiceProvider extends ServiceProvider
{
    /**
     * Register package services in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/log-viewer.php',
            'log-viewer'
        );

        $this->app->singleton(LogParserService::class, function ($app) {
            return new LogParserService;
        });

        $this->app->singleton('laravel-log-viewer', function ($app) {
            return new LogViewerService($app->make(LogParserService::class));
        });

        $this->app->alias('laravel-log-viewer', LogViewerService::class);
    }

    /**
     * Bootstrap package services, routes, views, and publishables.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'log-viewer');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendLogDigestMailCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/log-viewer.php' => config_path('log-viewer.php'),
            ], 'log-viewer-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/log-viewer'),
            ], 'log-viewer-views');
        }

        if (config('log-viewer.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }
}
