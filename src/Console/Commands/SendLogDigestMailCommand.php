<?php

namespace Ahsanrazib\LogViewer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Ahsanrazib\LogViewer\Mail\LogDigestMail;
use Ahsanrazib\LogViewer\Services\LogParserService;
use Ahsanrazib\LogViewer\Services\LogViewerService;

class SendLogDigestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-viewer:send-email-digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email digest of logs that occurred in the configured time interval';

    /**
     * Execute the console command.
     */
    public function handle(LogViewerService $service, LogParserService $parser): int
    {
        // 1. Check enabled configuration
        $enabled = (bool) Config::get('log-viewer.email_notifications.enabled', false);
        if (! $enabled) {
            $this->info('Log Viewer email notifications are disabled in config (email_notifications.enabled = false).');

            return self::SUCCESS;
        }

        // 2. Check recipient email
        $recipient = Config::get('log-viewer.email_notifications.to');
        if (empty($recipient)) {
            $this->warn('No recipient email address configured for Log Viewer notifications (email_notifications.to is empty).');

            return self::FAILURE;
        }

        // 3. Read interval and log levels
        $intervalMinutes = (int) Config::get('log-viewer.email_notifications.interval_minutes', 30);
        $levels = (array) Config::get('log-viewer.email_notifications.levels', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);

        // 4. Gather log files and collect matching entries
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

        // 5. Send Mail
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
