<?php

namespace Ahsanrazib\LogViewer\Support;

class LogEntry
{
    public function __construct(
        public string $id,
        public string $timestamp,
        public string $env,
        public string $level,
        public string $message,
        public array|string|null $context = null,
        public ?string $stackTrace = null,
        public ?string $raw = null,
        public int $lineNumber = 0
    ) {}

    public function levelClass(): string
    {
        return match (strtoupper($this->level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
            'WARNING' => 'warning',
            'NOTICE', 'INFO' => 'info',
            'DEBUG' => 'debug',
            default => 'secondary',
        };
    }

    public function levelBadgeColor(): string
    {
        return match (strtoupper($this->level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
            'WARNING' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            'NOTICE', 'INFO' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            'DEBUG' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            default => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'env' => $this->env,
            'level' => $this->level,
            'level_class' => $this->levelClass(),
            'message' => $this->message,
            'context' => $this->context,
            'stack_trace' => $this->stackTrace,
            'line_number' => $this->lineNumber,
        ];
    }
}
