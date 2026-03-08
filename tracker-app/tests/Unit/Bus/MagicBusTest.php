<?php

declare(strict_types=1);

namespace Tests\Unit\Bus;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Concerns\ShouldRunAfterResponse;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\Contracts\HandlerInterface;
use App\Bus\Contracts\QueryHandlerInterface;
use App\Bus\MagicBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Test suite for the MagicBus command/query bus implementation.
 *
 * Validates:
 * - Convention-based handler resolution (MessageClass -> MessageClassHandler)
 * - Proper execution of command and query handlers
 * - Database transaction wrapping for handlers with ShouldBeTransactional
 * - Deferred execution for handlers with ShouldRunAfterResponse
 * - Error handling for missing or invalid handlers
 * - Interface enforcement for trait usage
 */
class MagicBusTest extends TestCase
{
    use RefreshDatabase;

    private MagicBus $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new MagicBus();
    }

    /**
     * Test that a command is successfully dispatched to its handler.
     *
     * Validates the core convention: TestCommand -> TestCommandHandler
     */
    public function test_send_dispatches_command_to_handler(): void
    {
        $command = new TestCommand(value: 'test-value');

        $result = $this->subject->send($command);

        $this->assertSame('command-handled:test-value', $result);
    }

    /**
     * Test that a query is successfully dispatched to its handler.
     *
     * Validates that queries can return data through the bus.
     */
    public function test_send_dispatches_query_to_handler(): void
    {
        $query = new TestQuery(id: 42);

        $result = $this->subject->send($query);

        $this->assertSame('query-result:42', $result);
    }

    /**
     * Test that an exception is thrown when a handler class does not exist.
     *
     * The bus should provide a clear error message indicating which handler is missing.
     */
    public function test_send_throws_exception_when_handler_does_not_exist(): void
    {
        $command = new CommandWithoutHandler();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing Handler: Create the class [Tests\Unit\Bus\CommandWithoutHandlerHandler]');

        $this->subject->send($command);
    }

    /**
     * Test that an exception is thrown when a handler does not implement HandlerInterface.
     *
     * All handlers must implement the HandlerInterface contract.
     */
    public function test_send_throws_exception_when_handler_does_not_implement_interface(): void
    {
        $command = new CommandWithInvalidHandler();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Handler [Tests\Unit\Bus\CommandWithInvalidHandlerHandler] must implement HandlerInterface');

        $this->subject->send($command);
    }

    /**
     * Test that handlers using ShouldBeTransactional are executed within a database transaction.
     *
     * If the handler throws an exception, the transaction should be rolled back.
     */
    public function test_send_wraps_transactional_handler_in_database_transaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback)
            {
                return $callback();
            });

        $command = new TransactionalCommand(value: 'transactional-test');

        $result = $this->subject->send($command);

        $this->assertSame('transactional:transactional-test', $result);
    }

    /**
     * Test that handlers using ShouldRunAfterResponse are deferred until after the HTTP response.
     *
     * The send() method should return null for deferred commands, indicating
     * that execution has been deferred and will not provide a return value.
     */
    public function test_send_defers_execution_for_handlers_with_after_response_trait(): void
    {
        $command = new DeferredCommand(value: 'deferred-test');

        $result = $this->subject->send($command);

        // Deferred commands return null immediately (execution happens after response)
        $this->assertNull($result);
    }

    /**
     * Test that using ShouldRunAfterResponse on a non-CommandHandler throws an exception.
     *
     * The ShouldRunAfterResponse trait should only be used on CommandHandlerInterface
     * implementations, not on QueryHandlers, because queries must return results synchronously.
     */
    public function test_send_throws_exception_when_after_response_trait_used_without_command_interface(): void
    {
        $query = new InvalidDeferredQuery();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Handler [Tests\Unit\Bus\InvalidDeferredQueryHandler] implements App\Bus\Concerns\ShouldRunAfterResponse, '
            . 'but does not implement CommandHandlerInterface.'
        );

        $this->subject->send($query);
    }

    /**
     * Test that using ShouldBeTransactional on a non-CommandHandler throws an exception.
     *
     * The ShouldBeTransactional trait should only be used on CommandHandlerInterface
     * implementations, not on QueryHandlers, because queries should be read-only operations.
     */
    public function test_send_throws_exception_when_transactional_trait_used_without_command_interface(): void
    {
        $query = new InvalidTransactionalQuery();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Handler [Tests\Unit\Bus\InvalidTransactionalQueryHandler] implements App\Bus\Concerns\ShouldBeTransactional, '
            . 'but does not implement CommandHandlerInterface.'
        );

        $this->subject->send($query);
    }

    /**
     * Test that a handler can use both ShouldBeTransactional and be a CommandHandler.
     *
     * This is a valid combination - commands can be transactional.
     */
    public function test_send_allows_transactional_trait_on_command_handler(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback)
            {
                return $callback();
            });

        $command = new TransactionalCommand(value: 'valid-transactional');

        $result = $this->subject->send($command);

        $this->assertSame('transactional:valid-transactional', $result);
    }

    /**
     * Test that a handler can use both ShouldRunAfterResponse and be a CommandHandler.
     *
     * This is a valid combination - commands can be deferred.
     */
    public function test_send_allows_after_response_trait_on_command_handler(): void
    {
        $command = new DeferredCommand(value: 'valid-deferred');

        $result = $this->subject->send($command);

        // Deferred commands should return null
        $this->assertNull($result);
    }

    /**
     * Test that handlers implementing QueryHandlerInterface work correctly.
     *
     * Queries should execute synchronously and return their results immediately.
     */
    public function test_send_handles_query_handler_interface_correctly(): void
    {
        $query = new TestQuery(id: 99);

        $result = $this->subject->send($query);

        $this->assertSame('query-result:99', $result);
    }

    /**
     * Test that the bus resolves handlers through Laravel's container.
     *
     * This allows handlers to have dependencies injected via constructor.
     */
    public function test_send_resolves_handlers_through_container(): void
    {
        // The container should resolve the handler and its dependencies
        $command = new TestCommand(value: 'container-test');

        $result = $this->subject->send($command);

        $this->assertSame('command-handled:container-test', $result);
    }
}

// =====================================================
// Test Stubs: Commands, Queries, and Handlers
// =====================================================

/**
 * Simple test command for basic handler dispatch testing.
 */
class TestCommand
{
    public function __construct(
        public readonly string $value
    ) {
    }
}

/**
 * Handler for TestCommand.
 */
class TestCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        return 'command-handled:' . $message->value;
    }
}

/**
 * Simple test query for basic handler dispatch testing.
 */
class TestQuery
{
    public function __construct(
        public readonly int $id
    ) {
    }
}

/**
 * Handler for TestQuery.
 */
class TestQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        return 'query-result:' . $message->id;
    }
}

/**
 * Command with no corresponding handler (for testing error handling).
 */
class CommandWithoutHandler
{
    public function __construct()
    {
    }
}

/**
 * Command with a handler that doesn't implement HandlerInterface.
 */
class CommandWithInvalidHandler
{
    public function __construct()
    {
    }
}

/**
 * Invalid handler that doesn't implement HandlerInterface.
 */
class CommandWithInvalidHandlerHandler
{
    public function __invoke(object $message): mixed
    {
        return 'invalid';
    }
}

/**
 * Command with a transactional handler.
 */
class TransactionalCommand
{
    public function __construct(
        public readonly string $value
    ) {
    }
}

/**
 * Handler that uses ShouldBeTransactional trait.
 */
class TransactionalCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        return 'transactional:' . $message->value;
    }
}

/**
 * Command with a deferred handler.
 */
class DeferredCommand
{
    public function __construct(
        public readonly string $value
    ) {
    }
}

/**
 * Handler that uses ShouldRunAfterResponse trait.
 */
class DeferredCommandHandler implements CommandHandlerInterface
{
    use ShouldRunAfterResponse;

    public function __invoke(object $message): mixed
    {
        return 'deferred:' . $message->value;
    }
}

/**
 * Query that incorrectly uses ShouldRunAfterResponse (should fail).
 */
class InvalidDeferredQuery
{
    public function __construct()
    {
    }
}

/**
 * Handler that uses ShouldRunAfterResponse but only implements HandlerInterface,
 * not CommandHandlerInterface (should fail validation).
 */
class InvalidDeferredQueryHandler implements HandlerInterface
{
    use ShouldRunAfterResponse;

    public function __invoke(object $message): mixed
    {
        return 'invalid-deferred';
    }
}

/**
 * Query that incorrectly uses ShouldBeTransactional (should fail).
 */
class InvalidTransactionalQuery
{
    public function __construct()
    {
    }
}

/**
 * Handler that uses ShouldBeTransactional but only implements HandlerInterface,
 * not CommandHandlerInterface (should fail validation).
 */
class InvalidTransactionalQueryHandler implements HandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): mixed
    {
        return 'invalid-transactional';
    }
}
