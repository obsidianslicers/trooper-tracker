<?php

declare(strict_types=1);

namespace Tests\Unit\Bus;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Concerns\ShouldRunAfterResponse;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\Contracts\HandlerInterface;
use App\Bus\MagicBus;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Unit tests for MagicBus command/query dispatcher.
 *
 * Validates:
 * - Convention-based handler resolution (MessageClass + "Handler")
 * - Handler interface enforcement
 * - Container-based dependency injection
 * - Transaction wrapping for ShouldBeTransactional handlers
 * - Deferred execution for ShouldRunAfterResponse handlers
 * - Error handling for missing or invalid handlers
 */
class MagicBusTest extends TestCase
{
    public function test_send_successfully_dispatches_message_to_handler(): void
    {
        $subject = new MagicBus();
        $message = new TestCommand('test value');

        $result = $subject->send($message);

        $this->assertSame('handled: test value', $result);
    }

    public function test_send_throws_exception_when_handler_class_does_not_exist(): void
    {
        $subject = new MagicBus();
        $message = new TestCommandWithoutHandler('test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing Handler: Create the class [Tests\Unit\Bus\TestCommandWithoutHandlerHandler]');

        $subject->send($message);
    }

    public function test_send_throws_exception_when_handler_does_not_implement_interface(): void
    {
        $subject = new MagicBus();
        $message = new TestCommandWithInvalidHandler('test');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must implement HandlerInterface');

        $subject->send($message);
    }

    public function test_send_passes_message_object_to_handler_invoke_method(): void
    {
        $subject = new MagicBus();
        $message = new TestCommand('original value');

        $result = $subject->send($message);

        $this->assertStringContainsString('original value', $result);
    }

    public function test_send_resolves_handler_through_laravel_container(): void
    {
        $subject = new MagicBus();
        $message = new TestCommandWithDependency();

        // This will fail if the handler can't be resolved with its dependencies
        $result = $subject->send($message);

        $this->assertSame('handled with dependencies', $result);
    }

    public function test_send_returns_handler_result(): void
    {
        $subject = new MagicBus();
        $message = new TestCommandReturningArray();

        $result = $subject->send($message);

        $this->assertIsArray($result);
        $this->assertSame(['success' => true, 'data' => 'test'], $result);
    }

    public function test_send_works_with_void_return_type(): void
    {
        $subject = new MagicBus();
        $message = new TestCommandReturningVoid();

        $result = $subject->send($message);

        $this->assertNull($result);
    }

    public function test_send_wraps_handler_in_transaction_when_using_transactional_trait(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->with(\Mockery::type('Closure'))
            ->andReturnUsing(function ($callback)
            {
                return $callback();
            });

        $subject = new MagicBus();
        $message = new TestTransactionalCommand('test');

        $result = $subject->send($message);

        $this->assertSame('transactional: test', $result);
    }

    public function test_send_defers_execution_when_using_after_response_trait(): void
    {
        // Mock the app()->afterResponse() call
        $executed = false;
        app()->bind('afterResponse', function () use (&$executed)
        {
            return function ($callback) use (&$executed)
            {
                // Don't actually defer - just verify it would be called
                $executed = true;
            };
        });

        // Replace app() helper behavior for this test
        $this->app->macro('afterResponse', function ($callback)
        {
            // Just verify the callback was registered, don't execute it
            return null;
        });

        $subject = new MagicBus();
        $message = new TestDeferredCommand('deferred');

        $result = $subject->send($message);

        // Deferred commands return null immediately
        $this->assertNull($result);
    }

    public function test_send_throws_exception_when_after_response_handler_is_not_command_handler(): void
    {
        $subject = new MagicBus();
        $message = new TestInvalidDeferredQuery();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not implement CommandHandlerInterface');

        $subject->send($message);
    }

    public function test_send_throws_exception_when_transactional_handler_is_not_command_handler(): void
    {
        $subject = new MagicBus();
        $message = new TestInvalidTransactionalQuery();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not implement CommandHandlerInterface');

        $subject->send($message);
    }
}

// Test fixtures

class TestCommand
{
    public function __construct(public string $value)
    {
    }
}

class TestCommandHandler implements HandlerInterface
{
    public function __invoke(object $message): mixed
    {
        return 'handled: ' . $message->value;
    }
}

class TestCommandWithoutHandler
{
    public function __construct(public string $value)
    {
    }
}

class TestCommandWithInvalidHandler
{
    public function __construct(public string $value)
    {
    }
}

class TestCommandWithInvalidHandlerHandler
{
    // Does NOT implement HandlerInterface
    public function __invoke(object $message): mixed
    {
        return 'invalid';
    }
}

class TestCommandWithDependency
{
}

class TestCommandWithDependencyHandler implements HandlerInterface
{
    public function __construct(private TestDependency $dependency)
    {
    }

    public function __invoke(object $message): mixed
    {
        return 'handled with dependencies';
    }
}

class TestDependency
{
    // Simple dependency for testing container resolution
}

class TestCommandReturningArray
{
}

class TestCommandReturningArrayHandler implements HandlerInterface
{
    public function __invoke(object $message): mixed
    {
        return ['success' => true, 'data' => 'test'];
    }
}

class TestCommandReturningVoid
{
}

class TestCommandReturningVoidHandler implements HandlerInterface
{
    public function __invoke(object $message): mixed
    {
        // Void return (implicitly returns null)
        return null;
    }
}

// Transactional handler tests

class TestTransactionalCommand
{
    public function __construct(public string $value)
    {
    }
}

class TestTransactionalCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        return 'transactional: ' . $message->value;
    }
}

// Deferred execution tests

class TestDeferredCommand
{
    public function __construct(public string $value)
    {
    }
}

class TestDeferredCommandHandler implements CommandHandlerInterface
{
    use ShouldRunAfterResponse;

    public function __invoke(object $message): mixed
    {
        return 'deferred: ' . $message->value;
    }
}

// Invalid cases - traits on non-command handlers

class TestInvalidDeferredQuery
{
}

class TestInvalidDeferredQueryHandler implements HandlerInterface
{
    use ShouldRunAfterResponse;

    public function __invoke(object $message): mixed
    {
        return 'invalid';
    }
}

class TestInvalidTransactionalQuery
{
}

class TestInvalidTransactionalQueryHandler implements HandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        return 'invalid';
    }
}
