<?php

namespace Ahsanrazib\LogViewer\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class LogViewerService
{
    public function __construct(
        protected LogParserService $parser
    ) {}

    /**
     * Get storage path for logs from config.
     */
    public function getStoragePath(): string
    {
        return Config::get('log-viewer.storage_path', storage_path('logs'));
    }

    /**
     * Get list of all available log files.
     */
    public function getFiles(): array
    {
        return $this->parser->getLogFiles($this->getStoragePath());
    }

    /**
     * Get parsed log entries with pagination, stats, and search.
     */
    public function getLogs(
        ?string $fileName = null,
        array|string|null $level = null,
        ?string $query = null,
        int $page = 1,
        ?int $perPage = null
    ): array {
        $files = $this->getFiles();

        if (empty($files)) {
            return [
                'file' => null,
                'files' => [],
                'entries' => [],
                'total' => 0,
                'per_page' => $perPage ?? Config::get('log-viewer.per_page', 50),
                'current_page' => 1,
                'last_page' => 1,
                'stats' => [
                    'ALL' => 0,
                    'EMERGENCY' => 0,
                    'ALERT' => 0,
                    'CRITICAL' => 0,
                    'ERROR' => 0,
                    'WARNING' => 0,
                    'NOTICE' => 0,
                    'INFO' => 0,
                    'DEBUG' => 0,
                ],
            ];
        }

        $selectedFile = null;
        if ($fileName) {
            foreach ($files as $f) {
                if ($f['name'] === $fileName) {
                    $selectedFile = $f;
                    break;
                }
            }
        }

        if (! $selectedFile) {
            $selectedFile = $files[0];
        }

        $perPage = $perPage ?? Config::get('log-viewer.per_page', 50);

        $parsed = $this->parser->parseFile(
            filePath: $selectedFile['path'],
            levelFilter: $level,
            searchQuery: $query,
            page: $page,
            perPage: $perPage
        );

        return array_merge([
            'file' => $selectedFile,
            'files' => $files,
        ], $parsed);
    }

    /**
     * Clear content of a log file.
     */
    public function clearLogFile(string $fileName): bool
    {
        $filePath = $this->resolveFilePath($fileName);

        if (File::exists($filePath)) {
            File::put($filePath, '');

            return true;
        }

        return false;
    }

    /**
     * Delete a log file.
     */
    public function deleteLogFile(string $fileName): bool
    {
        $filePath = $this->resolveFilePath($fileName);

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    /**
     * Resolve and validate safety of log file path.
     */
    public function resolveFilePath(string $fileName): string
    {
        // Path traversal protection
        $sanitizedName = basename($fileName);
        $fullPath = $this->getStoragePath().DIRECTORY_SEPARATOR.$sanitizedName;

        if (! File::exists($fullPath)) {
            throw new InvalidArgumentException("Log file '{$sanitizedName}' does not exist.");
        }

        return $fullPath;
    }
}
