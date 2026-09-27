<?php

namespace SolverCircle\LogViewer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use SolverCircle\LogViewer\Mail\LogDigestMail;
use SolverCircle\LogViewer\Services\LogParserService;
use SolverCircle\LogViewer\Services\LogViewerService;

class SendLogDigestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-viewer:send-email-digest {--force : Force execution even outside production environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email digest of logs that occurred in the configured time interval (only in production)';

    /**
     * Execute the console command.
     */
    public function handle(LogViewerService $service, LogParserService $parser): int
    {
        // 1. Environment constraint check: Production only unless --force is specified
        if (! App::environment('production') && ! $this->option('force')) {
            $this->info("Log Viewer email notifications are configured to run only in 'production' environment (current: '".App::environment()."'). Use --force to override.");

            return self::SUCCESS;
        }

        // 2. Check enabled configuration
        $enabled = (bool) Config::get('log-viewer.email_notifications.enabled', false);
        if (! $enabled) {
            $this->info('Log Viewer email notifications are disabled in config (email_notifications.enabled = false).');

            return self::SUCCESS;
        }

        // 3. Check recipient email
        $recipient = Config::get('log-viewer.email_notifications.to');
        if (empty($recipient)) {
            $this->warn('No recipient email address configured for Log Viewer notifications (email_notifications.to is empty).');

            return self::FAILURE;
        }

        // 4. Read interval and log levels
        $intervalMinutes = (int) Config::get('log-viewer.email_notifications.interval_minutes', 30);
        $levels = (array) Config::get('log-viewer.email_notifications.levels', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);

        // 5. Gather log files and collect matching entries
        $files = $service->getFiles();
        if (empty($files)) {
            $this->info('No log files available to parse.');

            return self::SUCCESS;
        }

        $allMatchingEntries = [];
        foreach ($files as $file) {
            $entries = $parser->getLogsInTimeframe($file['path'], $intervalMinutes, $levels);
            $allMatchingEntries = array_merge($allMatchingEntries, $entries);
        }

        if (empty($allMatchingEntries)) {
            $this->info("No matching log entries found in the last {$intervalMinutes} minutes.");

            return self::SUCCESS;
        }

        // 6. Send Mail
        $environment = App::environment();
        Mail::to($recipient)->send(new LogDigestMail(
            entries: $allMatchingEntries,
            intervalMinutes: $intervalMinutes,
            environment: $environment,
            levels: $levels
        ));

        $count = count($allMatchingEntries);
        $recipientStr = is_array($recipient) ? implode(', ', $recipient) : $recipient;
        $this->info("Successfully sent log digest email to '{$recipientStr}' with {$count} log entry(ies).");

        return self::SUCCESS;
    }
}
