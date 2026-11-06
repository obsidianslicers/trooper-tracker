<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\Fixtures\Isolation\BrokenMiddleware;
use Tests\Fixtures\Isolation\IsolationProvider;
use Tests\TestCase;

class PluginIsolationTest extends TestCase
{
    private IsolationProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new IsolationProvider($this->app);
        BrokenMiddleware::$mode = 'throw';
    }

    public function test_a_failing_listener_does_not_stop_the_event(): void
    {
        Log::spy();
        $ran = false;
        $this->subject->listenTo('isolation.event', function (): void
        {
            throw new RuntimeException('Discord is down.');
        });
        Event::listen('isolation.event', function () use (&$ran): void
        {
            $ran = true;
        });

        Event::dispatch('isolation.event');

        $this->assertTrue($ran);
        $this->assertLoggedPluginFailure('Discord is down.');
    }

    public function test_a_listener_still_receives_the_payload_and_can_halt(): void
    {
        $received = null;
        $this->subject->listenTo('isolation.until', function (string $value) use (&$received): bool
        {
            $received = $value;

            return false;
        });

        $result = Event::until('isolation.until', ['payload']);

        $this->assertSame('payload', $received);
        $this->assertFalse($result);
    }

    public function test_a_failing_middleware_lets_the_request_through(): void
    {
        Log::spy();
        $this->subject->addMiddleware(BrokenMiddleware::class);
        Route::middleware('web')->get('/isolation-test', fn (): string => 'page');

        $this->get('/isolation-test')->assertOk()->assertSee('page');

        $this->assertLoggedPluginFailure('The forum is down.');
    }

    public function test_a_middleware_can_still_refuse_the_request(): void
    {
        BrokenMiddleware::$mode = 'abort';
        $this->subject->addMiddleware(BrokenMiddleware::class);
        Route::middleware('web')->get('/isolation-test', fn (): string => 'page');

        $this->get('/isolation-test')->assertForbidden();
    }

    public function test_errors_after_the_middleware_are_not_swallowed(): void
    {
        BrokenMiddleware::$mode = 'pass';
        $calls = 0;
        $this->subject->addMiddleware(BrokenMiddleware::class);
        Route::middleware('web')->get('/isolation-test', function () use (&$calls): void
        {
            $calls++;

            throw new RuntimeException('Core page failed.');
        });

        $this->get('/isolation-test')->assertServerError();

        $this->assertSame(1, $calls);
    }

    private function assertLoggedPluginFailure(string $error): void
    {
        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_starts_with($message, 'Plugin code failed')
                && str_contains($message, $error))
            ->once();
    }
}
