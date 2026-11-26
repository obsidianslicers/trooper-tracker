<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LogEntry;
use App\Services\LogViewerService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LogViewerServiceTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-26 12:00:00');
        $this->storage = sys_get_temp_dir().'/log-viewer-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/logs');
        $this->app->useStoragePath($this->storage);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_recent_errors_returns_the_newest_errors_first(): void
    {
        $this->writeLog('laravel.log', [
            $this->line('2026-11-20 10:00:00', 'error', 'First'),
            $this->line('2026-11-21 10:00:00', 'info', 'Not an error'),
            $this->line('2026-11-22 10:00:00', 'critical', "Second\n#0 trace"),
        ]);

        $subject = new LogViewerService;
        $result = $subject->recentErrors(10);

        $this->assertSame(["Second\n#0 trace", 'First'], $result->map(fn (LogEntry $e) => $e->message)->all());
        $this->assertSame('CRITICAL', $result[0]->level);
        $this->assertSame('2026-11-22 10:00:00', $result[0]->logged_at);
    }

    public function test_recent_errors_stops_at_the_limit(): void
    {
        $lines = [];
        for ($day = 1; $day <= 20; $day++)
        {
            $lines[] = $this->line(sprintf('2026-11-%02d 10:00:00', $day), 'error', "Error {$day}");
        }
        $this->writeLog('laravel.log', $lines);

        $subject = new LogViewerService;
        $result = $subject->recentErrors(3);

        $this->assertSame(['Error 20', 'Error 19', 'Error 18'], $result->map(fn (LogEntry $e) => $e->message)->all());
    }

    public function test_recent_errors_leaves_out_errors_older_than_thirty_days(): void
    {
        $this->writeLog('laravel.log', [
            $this->line('2026-10-01 10:00:00', 'error', 'Too old'),
            $this->line('2026-11-25 10:00:00', 'error', 'Recent'),
        ]);

        $subject = new LogViewerService;
        $result = $subject->recentErrors(10);

        $this->assertSame(['Recent'], $result->map(fn (LogEntry $e) => $e->message)->all());
    }

    public function test_recent_errors_keeps_an_entry_that_spans_several_chunks_whole(): void
    {
        $trace = implode("\n", array_map(fn (int $i) => "#{$i} ".str_repeat('x', 100), range(0, 4000)));
        $this->writeLog('laravel.log', [
            $this->line('2026-11-24 10:00:00', 'error', "Long\n".$trace),
            $this->line('2026-11-25 10:00:00', 'error', 'Short'),
        ]);

        $subject = new LogViewerService;
        $result = $subject->recentErrors(10);

        $this->assertCount(2, $result);
        $this->assertSame('Long', $result[1]->summary());
        $this->assertStringStartsWith("Long\n#0 x", $result[1]->message);
        $this->assertLessThanOrEqual(20_003, mb_strlen($result[1]->message));
    }

    public function test_recent_errors_merges_daily_files(): void
    {
        $this->writeLog('laravel-2026-11-24.log', [$this->line('2026-11-24 10:00:00', 'error', 'Monday')]);
        $this->writeLog('laravel-2026-11-25.log', [$this->line('2026-11-25 10:00:00', 'error', 'Tuesday')]);

        $subject = new LogViewerService;
        $result = $subject->recentErrors(10);

        $this->assertSame(['Tuesday', 'Monday'], $result->map(fn (LogEntry $e) => $e->message)->all());
    }

    public function test_recent_errors_is_empty_without_a_log(): void
    {
        $subject = new LogViewerService;

        $this->assertCount(0, $subject->recentErrors(10));
    }

    private function line(string $logged_at, string $level, string $message): string
    {
        return "[{$logged_at}] production.".strtoupper($level).": {$message}";
    }

    /**
     * @param array<int, string> $lines
     */
    private function writeLog(string $name, array $lines): void
    {
        $path = $this->storage.'/logs/'.$name;
        File::put($path, implode("\n", $lines)."\n");
        touch($path, CarbonImmutable::now()->getTimestamp());
    }
}
