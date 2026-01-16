<?php

declare(strict_types=1);

namespace Tests\Unit\Bus;

use App\Bus\Contracts\HandlerInterface;
use App\Bus\MagicBus;
use RuntimeException;
use Tests\TestCase;

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
        $this->expectExceptionMessage('must implement ' . HandlerInterface::class);

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
