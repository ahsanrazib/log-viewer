<?php

namespace Ahsanrazib\LogViewer\Facades;

use Illuminate\Support\Facades\Facade;
use Ahsanrazib\LogViewer\Services\LogViewerService;

/**
 * @method static array getFiles()
 * @method static array getLogs(?string $fileName = null, ?string $level = null, ?string $query = null, int $page = 1, ?int $perPage = null)
 * @method static bool clearLogFile(string $fileName)
 * @method static bool deleteLogFile(string $fileName)
 * @method static string resolveFilePath(string $fileName)
 *
 * @see LogViewerService
 */
class LogViewer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-log-viewer';
    }
}
