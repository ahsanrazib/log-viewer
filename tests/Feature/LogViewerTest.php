<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\File;
use SolverCircle\LogViewer\Services\LogParserService;
use SolverCircle\LogViewer\Services\LogViewerService;
use SolverCircle\LogViewer\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->withoutMiddleware([ValidateCsrfToken::class]);
    config(['log-viewer.passkey' => null]);
    $this->logDir = storage_path('logs');
    if (! File::isDirectory($this->logDir)) {
        File::makeDirectory($this->logDir, 0755, true);
    }
    $this->sampleLogPath = $this->logDir.'/test-laravel.log';
});

afterEach(function () {
    if (File::exists($this->sampleLogPath)) {
        File::delete($this->sampleLogPath);
    }
});

test('it lists log files in storage/logs', function () {
    File::put($this->sampleLogPath, "[2026-09-27 10:00:00] testing.INFO: Test log entry {}\n");

    /** @var LogViewerService $service */
    $service = app(LogViewerService::class);
    $files = $service->getFiles();

    expect($files)->not->toBeEmpty();
    $fileNames = array_column($files, 'name');
    expect($fileNames)->toContain('test-laravel.log');
});

test('it correctly parses monolog entries with levels, context, and stack traces', function () {
    $dummyLog = <<<'LOG'
[2026-09-27 10:15:00] local.ERROR: Failed to connect to database {"host":"127.0.0.1","port":5432}
[stacktrace]
#0 /app/DatabaseConnector.php(42): PDO->__construct()
#1 /app/Http/Controllers/UserController.php(15): DatabaseConnector->connect()

[2026-09-27 10:16:00] local.WARNING: High memory usage detected {"usage_mb":128}

[2026-09-27 10:17:00] local.INFO: User logged in successfully {"user_id":42}
LOG;

    File::put($this->sampleLogPath, $dummyLog);

    /** @var LogParserService $parser */
    $parser = app(LogParserService::class);
    $result = $parser->parseFile($this->sampleLogPath);

    expect($result['total'])->toBe(3);
    expect($result['stats']['ERROR'])->toBe(1);
    expect($result['stats']['WARNING'])->toBe(1);
    expect($result['stats']['INFO'])->toBe(1);

    $errorEntry = $result['entries'][2];
    expect($errorEntry['level'])->toBe('ERROR');
    expect($errorEntry['message'])->toBe('Failed to connect to database');
    expect($errorEntry['context'])->toBe(['host' => '127.0.0.1', 'port' => 5432]);
    expect($errorEntry['stack_trace'])->toContain('#0 /app/DatabaseConnector.php(42)');
});

test('it filters log entries by level', function () {
    $dummyLog = <<<'LOG'
[2026-09-27 10:15:00] local.ERROR: First error
[2026-09-27 10:16:00] local.INFO: Information message
[2026-09-27 10:17:00] local.ERROR: Second error
LOG;

    File::put($this->sampleLogPath, $dummyLog);

    /** @var LogParserService $parser */
    $parser = app(LogParserService::class);
    $errorResult = $parser->parseFile($this->sampleLogPath, levelFilter: 'ERROR');

    expect($errorResult['total'])->toBe(2);
    foreach ($errorResult['entries'] as $entry) {
        expect($entry['level'])->toBe('ERROR');
    }
});

test('it filters log entries by multiple selected log levels', function () {
    $dummyLog = <<<'LOG'
[2026-09-27 10:15:00] local.ERROR: First error entry
[2026-09-27 10:16:00] local.INFO: Information log message
[2026-09-27 10:17:00] local.DEBUG: Debugging message entry
[2026-09-27 10:18:00] local.WARNING: Warning alert entry
LOG;

    File::put($this->sampleLogPath, $dummyLog);

    /** @var LogParserService $parser */
    $parser = app(LogParserService::class);
    $multiResult = $parser->parseFile($this->sampleLogPath, levelFilter: ['ERROR', 'DEBUG']);

    expect($multiResult['total'])->toBe(2);
    $levelsFound = array_column($multiResult['entries'], 'level');
    expect($levelsFound)->toContain('ERROR');
    expect($levelsFound)->toContain('DEBUG');
    expect($levelsFound)->not->toContain('INFO');
    expect($levelsFound)->not->toContain('WARNING');
});

test('it searches log entries by query string', function () {
    $dummyLog = <<<'LOG'
[2026-09-27 10:15:00] local.ERROR: Payment gateway timeout {"gateway":"Stripe"}
[2026-09-27 10:16:00] local.INFO: User updated profile {"user_id":99}
LOG;

    File::put($this->sampleLogPath, $dummyLog);

    /** @var LogParserService $parser */
    $parser = app(LogParserService::class);
    $searchResult = $parser->parseFile($this->sampleLogPath, searchQuery: 'Stripe');

    expect($searchResult['total'])->toBe(1);
    expect($searchResult['entries'][0]['message'])->toContain('Payment gateway timeout');
});

test('it renders the log viewer UI page', function () {
    File::put($this->sampleLogPath, "[2026-09-27 12:00:00] local.INFO: UI Page render test {}\n");

    $response = $this->get('/log-viewer?file=test-laravel.log');

    $response->assertStatus(200);
    $response->assertSee('Laravel Log Viewer');
    $response->assertSee('test-laravel.log');
    $response->assertSee('UI Page render test');
});

test('it returns JSON data from the API endpoint', function () {
    File::put($this->sampleLogPath, "[2026-09-27 12:05:00] local.WARNING: API response test {}\n");

    $response = $this->getJson('/log-viewer/api/logs?file=test-laravel.log');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'file',
        'files',
        'entries',
        'total',
        'stats',
    ]);
    $response->assertJsonFragment(['level' => 'WARNING']);
});

test('it downloads a log file', function () {
    File::put($this->sampleLogPath, "Sample log content for download\n");

    $response = $this->get('/log-viewer/download/test-laravel.log');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

test('it clears log file content', function () {
    File::put($this->sampleLogPath, "Log content to be cleared\n");

    $response = $this->postJson('/log-viewer/clear/test-laravel.log');

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect(File::get($this->sampleLogPath))->toBe('');
});

test('it deletes a log file', function () {
    File::put($this->sampleLogPath, "Log content to be deleted\n");

    $response = $this->deleteJson('/log-viewer/delete/test-laravel.log');

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect(File::exists($this->sampleLogPath))->toBeFalse();
});

test('it protects against path traversal when accessing log files', function () {
    $this->expectException(InvalidArgumentException::class);

    /** @var LogViewerService $service */
    $service = app(LogViewerService::class);
    $service->resolveFilePath('../../etc/passwd');
});

test('it renders passkey prompt when LOG_VIEWER_PASSKEY is configured and user is unauthenticated', function () {
    config(['log-viewer.passkey' => 'super-secret-key']);

    $response = $this->get('/log-viewer');

    $response->assertStatus(401);
    $response->assertSee('Protected Log Viewer');
    $response->assertSee('Security Passkey');
});

test('it authenticates successfully with correct passkey', function () {
    config(['log-viewer.passkey' => 'super-secret-key']);

    $response = $this->post('/log-viewer/auth', [
        'passkey' => 'super-secret-key',
    ]);

    $response->assertRedirect('/log-viewer');
    $this->get('/log-viewer')->assertStatus(200);
});

test('it rejects invalid passkey submission', function () {
    config(['log-viewer.passkey' => 'super-secret-key']);

    $response = $this->post('/log-viewer/auth', [
        'passkey' => 'wrong-key',
    ]);

    $response->assertSessionHas('error', 'Invalid passkey provided.');
});

test('it locks passkey session when lock endpoint is hit', function () {
    config(['log-viewer.passkey' => 'super-secret-key']);
    session(['log_viewer_passkey_authorized' => true]);

    $response = $this->post('/log-viewer/lock');

    $response->assertRedirect('/log-viewer');
    $this->get('/log-viewer')->assertStatus(401);
});

test('it blocks JSON API requests when unauthenticated passkey is set', function () {
    config(['log-viewer.passkey' => 'super-secret-key']);

    $response = $this->getJson('/log-viewer/api/logs');

    $response->assertStatus(401);
    $response->assertJson(['passkey_required' => true]);
});
