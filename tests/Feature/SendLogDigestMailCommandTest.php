<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use SolverCircle\LogViewer\Mail\LogDigestMail;
use SolverCircle\LogViewer\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Mail::fake();
    $this->logDir = storage_path('logs');
    if (! File::isDirectory($this->logDir)) {
        File::makeDirectory($this->logDir, 0755, true);
    }
    $this->sampleLogPath = $this->logDir.'/test-email-laravel.log';
});

afterEach(function () {
    if (File::exists($this->sampleLogPath)) {
        File::delete($this->sampleLogPath);
    }
});

test('it skips email digest when email_notifications.enabled is false', function () {
    config([
        'log-viewer.email_notifications.enabled' => false,
        'log-viewer.email_notifications.to' => 'admin@example.com',
    ]);

    $this->artisan('log-viewer:send-email-digest')
        ->expectsOutputToContain('email notifications are disabled in config')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

test('it warns when no recipient email address is configured', function () {
    config([
        'log-viewer.email_notifications.enabled' => true,
        'log-viewer.email_notifications.to' => null,
    ]);

    $this->artisan('log-viewer:send-email-digest')
        ->expectsOutputToContain('No recipient email address configured')
        ->assertExitCode(1);

    Mail::assertNothingSent();
});

test('it sends email digest when matching logs exist in timeframe in any APP_ENV', function () {
    config([
        'log-viewer.email_notifications.enabled' => true,
        'log-viewer.email_notifications.to' => 'admin@example.com',
        'log-viewer.email_notifications.interval_minutes' => 30,
        'log-viewer.email_notifications.levels' => ['ERROR'],
    ]);

    $now = date('Y-m-d H:i:s');
    File::put($this->sampleLogPath, "[{$now}] local.ERROR: Database query failed {}\n");

    $this->artisan('log-viewer:send-email-digest')
        ->expectsOutputToContain("Successfully sent log digest email to 'admin@example.com'")
        ->assertSuccessful();

    Mail::assertSent(LogDigestMail::class, function (LogDigestMail $mail) {
        return count($mail->entries) === 1 &&
               $mail->entries[0]['message'] === 'Database query failed' &&
               $mail->hasTo('admin@example.com');
    });
});

test('it does not send email digest when no logs match the timeframe', function () {
    config([
        'log-viewer.email_notifications.enabled' => true,
        'log-viewer.email_notifications.to' => 'admin@example.com',
        'log-viewer.email_notifications.interval_minutes' => 30,
        'log-viewer.email_notifications.levels' => ['ERROR'],
    ]);

    // Old log from 2 hours ago
    $oldTime = date('Y-m-d H:i:s', time() - 7200);
    File::put($this->sampleLogPath, "[{$oldTime}] local.ERROR: Old database error {}\n");

    $this->artisan('log-viewer:send-email-digest')
        ->expectsOutputToContain('No matching log entries found in the last 30 minutes')
        ->assertSuccessful();

    Mail::assertNothingSent();
});
