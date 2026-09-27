<?php

namespace SolverCircle\LogViewer\Services;

use Illuminate\Support\Facades\File;
use SolverCircle\LogViewer\Support\LogEntry;

class LogParserService
{
    /**
     * Regex pattern to match standard Laravel Monolog entries.
     */
    protected const LOG_HEADER_PATTERN = '/^\[(?P<date>\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+-]\d{2}:\d{2}|Z)?)\]\s+(?P<env>[a-zA-Z0-9_\-]+)\.(?P<level>[A-Z]+):\s+(?P<message>.*)$/s';

    /**
     * List all log files in the specified directory.
     *
     * @return array<int, array{name: string, path: string, size: int, size_formatted: string, updated_at: string}>
     */
    public function getLogFiles(string $storagePath): array
    {
        if (! File::isDirectory($storagePath)) {
            return [];
        }

        $files = File::files($storagePath);
        $logFiles = [];

        foreach ($files as $file) {
            if (in_array($file->getExtension(), ['log', 'txt'])) {
                $size = $file->getSize();
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getRealPath(),
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'updated_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        // Sort by modification time descending
        usort($logFiles, fn ($a, $b) => strcmp($b['updated_at'], $a['updated_at']));

        return $logFiles;
    }

    /**
     * Parse log file into structured LogEntry DTOs with filtering, search, and pagination.
     *
     * @param  array<int, string>|string|null  $levelFilter
     */
    public function parseFile(
        string $filePath,
        array|string|null $levelFilter = null,
        ?string $searchQuery = null,
        int $page = 1,
        int $perPage = 50
    ): array {
        if (! File::exists($filePath)) {
            return [
                'entries' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1,
                'stats' => $this->getEmptyStats(),
            ];
        }

        // Normalize level filter into array of uppercase strings
        $levels = is_array($levelFilter)
            ? $levelFilter
            : (is_string($levelFilter) && trim($levelFilter) !== '' ? explode(',', $levelFilter) : []);

        $upperFilterLevels = array_map('strtoupper', array_map('trim', array_filter($levels)));

        $content = File::get($filePath);
        $rawBlocks = $this->splitLogBlocks($content);

        $entries = [];
        $stats = $this->getEmptyStats();
        $lineNumber = 1;

        foreach ($rawBlocks as $block) {
            $entry = $this->parseBlock($block, $lineNumber++);

            if (! $entry) {
                continue;
            }

            // Track level statistics
            $upperLevel = strtoupper($entry->level);
            if (array_key_exists($upperLevel, $stats)) {
                $stats[$upperLevel]++;
            }
            $stats['ALL']++;

            // Apply Multi-Level Filter
            if (! empty($upperFilterLevels) && ! in_array('ALL', $upperFilterLevels, true) && ! in_array($upperLevel, $upperFilterLevels, true)) {
                continue;
            }

            // Apply Search Query Filter
            if ($searchQuery !== null && trim($searchQuery) !== '') {
                $needle = strtolower(trim($searchQuery));
                $matchMessage = str_contains(strtolower($entry->message), $needle);
                $matchContext = $entry->context && str_contains(strtolower(is_array($entry->context) ? json_encode($entry->context) : $entry->context), $needle);
                $matchTrace = $entry->stackTrace && str_contains(strtolower($entry->stackTrace), $needle);

                if (! ($matchMessage || $matchContext || $matchTrace)) {
                    continue;
                }
            }

            $entries[] = $entry;
        }

        // Newer entries first
        $entries = array_reverse($entries);

        $total = count($entries);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $paginatedEntries = array_slice($entries, $offset, $perPage);

        return [
            'entries' => array_map(fn (LogEntry $entry) => $entry->toArray(), $paginatedEntries),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'stats' => $stats,
        ];
    }

    /**
     * Get log entries created within the last X minutes matching specified log levels.
     *
     * @param  array<int, string>  $levels
     * @return array<int, array>
     */
    public function getLogsInTimeframe(string $filePath, int $minutes = 30, array $levels = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']): array
    {
        if (! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $rawBlocks = $this->splitLogBlocks($content);

        $cutoffTimestamp = time() - ($minutes * 60);
        $upperLevels = array_map('strtoupper', $levels);
        $matchedEntries = [];
        $lineNumber = 1;

        foreach ($rawBlocks as $block) {
            $entry = $this->parseBlock($block, $lineNumber++);

            if (! $entry) {
                continue;
            }

            if (! in_array(strtoupper($entry->level), $upperLevels, true)) {
                continue;
            }

            $entryTime = strtotime($entry->timestamp);
            if ($entryTime !== false && $entryTime >= $cutoffTimestamp) {
                $matchedEntries[] = $entry->toArray();
            }
        }

        return array_reverse($matchedEntries);
    }

    /**
     * Split continuous log file content into individual log entry text blocks.
     *
     * @return array<int, string>
     */
    protected function splitLogBlocks(string $content): array
    {
        $lines = explode("\n", $content);
        $blocks = [];
        $currentBlock = '';

        foreach ($lines as $line) {
            // Check if line starts with a new log entry header [YYYY-MM-DD ...]
            if (preg_match('/^\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/', $line)) {
                if (trim($currentBlock) !== '') {
                    $blocks[] = trim($currentBlock);
                }
                $currentBlock = $line;
            } else {
                $currentBlock .= "\n".$line;
            }
        }

        if (trim($currentBlock) !== '') {
            $blocks[] = trim($currentBlock);
        }

        return $blocks;
    }

    /**
     * Parse a single log block into a LogEntry model.
     */
    protected function parseBlock(string $block, int $lineNumber): ?LogEntry
    {
        $firstLineEnd = strpos($block, "\n");
        $headerLine = $firstLineEnd !== false ? substr($block, 0, $firstLineEnd) : $block;
        $body = $firstLineEnd !== false ? substr($block, $firstLineEnd + 1) : '';

        if (! preg_match(self::LOG_HEADER_PATTERN, $headerLine, $matches)) {
            return null;
        }

        $date = $matches['date'];
        $env = $matches['env'];
        $level = $matches['level'];
        $fullMessage = trim($matches['message']);

        // Check for embedded JSON context or stacktrace in body/message
        $context = null;
        $stackTrace = null;

        if ($body !== '') {
            if (str_contains($body, '[stacktrace]') || str_contains($body, '#0 ')) {
                $parts = preg_split('/(\[stacktrace\]|#0\s+)/', $body, 2, PREG_SPLIT_DELIM_CAPTURE);
                $contextCandidate = trim($parts[0] ?? '');
                $stackTrace = trim(($parts[1] ?? '').($parts[2] ?? ''));

                if ($contextCandidate !== '') {
                    $context = $this->tryParseJson($contextCandidate) ?? $contextCandidate;
                }
            } else {
                $context = $this->tryParseJson($body) ?? trim($body);
            }
        }

        // Also check if message itself ends with JSON context e.g. {"foo":"bar"}
        if ($context === null && preg_match('/(\{.*\})$/s', $fullMessage, $jsonMatch)) {
            $parsedJson = $this->tryParseJson($jsonMatch[1]);
            if ($parsedJson !== null) {
                $context = $parsedJson;
                $fullMessage = trim(substr($fullMessage, 0, -strlen($jsonMatch[1])));
            }
        }

        $id = md5($headerLine.$lineNumber);

        return new LogEntry(
            id: $id,
            timestamp: $date,
            env: $env,
            level: $level,
            message: $fullMessage,
            context: $context,
            stackTrace: $stackTrace,
            raw: $block,
            lineNumber: $lineNumber
        );
    }

    /**
     * Attempt to parse string as JSON array/object.
     */
    protected function tryParseJson(string $data): mixed
    {
        $trimmed = trim($data);
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get empty level stats counts.
     *
     * @return array<string, int>
     */
    protected function getEmptyStats(): array
    {
        return [
            'ALL' => 0,
            'EMERGENCY' => 0,
            'ALERT' => 0,
            'CRITICAL' => 0,
            'ERROR' => 0,
            'WARNING' => 0,
            'NOTICE' => 0,
            'INFO' => 0,
            'DEBUG' => 0,
        ];
    }
}
