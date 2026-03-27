<?php

declare(strict_types=1);

namespace App\Bus;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Concerns\ShouldRunAfterResponse;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\Contracts\HandlerInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Simple Command/Query bus using convention-based handler resolution.
 *
 * MagicBus automatically routes Commands and Queries to their corresponding
 * handlers using a naming convention: for a message class "MyTask", it will
 * look for a handler class "MyTaskHandler".
 *
 * This pattern promotes:
 * - Single Responsibility Principle: One handler per Command/Query
 * - Dependency Injection: Handlers are resolved through Laravel's container
 * - Testability: Handlers can be easily unit tested in isolation
 *
 * Example:
 * ```php
 * // Command
 * class CreateUserCommand {
 *     public function __construct(
 *         public string $email,
 *         public string $name
 *     ) {}
 * }
 *
 * // Handler
 * class CreateUserCommandHandler implements HandlerInterface {
 *     public function __invoke(CreateUserCommand $command): User {
 *         return User::create([
 *             'email' => $command->email,
 *             'name' => $command->name,
 *         ]);
 *     }
 * }
 *
 * // Usage
 * $user = $bus->send(new CreateUserCommand('test@example.com', 'Test'));
 * ```
 *
 * @see HandlerInterface
 * @see ShouldRunAfterResponse
 */
class MagicBus
{
    /**
     * Sends a Command or Query to its corresponding Handler.
     *
     * The handler is resolved using the naming convention:
     * MessageClass + "Handler" = HandlerClass
     *
     * For example:
     * - MyCommand -> MyCommandHandler
     * - GetUserQuery -> GetUserQueryHandler
     *
     * Handler behavior modifiers:
     * - Handlers using ShouldBeTransactional trait: Wrapped in a database transaction
     * - Handlers using ShouldRunAfterResponse trait: Executed after HTTP response is sent (Commands only)
     *
     * @param  object  $message  The Command or Query object to dispatch
     * @return mixed The result returned by the handler's __invoke method, or null for deferred commands
     *
     * @throws RuntimeException If the handler class does not exist
     * @throws RuntimeException If the handler does not implement HandlerInterface
     * @throws RuntimeException If handler uses ShouldRunAfterResponse but is not a CommandHandlerInterface
     */
    public function send(object $message): mixed
    {
        // Convention: MyTask -> MyTaskHandler
        $handler_class = get_class($message).'Handler';

        if (!class_exists($handler_class))
        {
            throw new RuntimeException("Missing Handler: Create the class [{$handler_class}]");
        }

        $handler = App::make($handler_class);

        if (!$handler instanceof HandlerInterface)
        {
            throw new RuntimeException("Handler [$handler_class] must implement HandlerInterface");
        }

        if ($this->usesTrait($handler_class, ShouldBeTransactional::class))
        {
            return DB::transaction(fn () => $handler($message));
        }

        if ($this->usesTrait($handler_class, ShouldRunAfterResponse::class))
        {
            // Defer execution until after the HTTP response is sent
            dispatch(function () use ($handler, $message) {
                $handler($message);
            })->afterResponse();

            // Deferred commands do not return a value
            return null;
        }

        // Executes the __invoke method in the handler
        return $handler($message);
    }

    /**
     * Checks if a handler class uses a specific trait.
     *
     * This method recursively checks the handler class and all its parent classes
     * for the specified trait. If the trait is found and it's a concern trait,
     * it validates that the handler properly implements CommandHandlerInterface.
     *
     * @param  string  $handler_class  The fully qualified handler class name
     * @param  string  $trait  The fully qualified trait name to check for
     * @return bool True if the handler uses the trait, false otherwise
     *
     * @throws RuntimeException If trait requires CommandHandlerInterface but handler doesn't implement it
     */
    private function usesTrait(string $handler_class, string $trait): bool
    {
        $result = in_array($trait, class_uses_recursive($handler_class), true);

        if ($result)
        {
            $this->checkForCommandInterface($handler_class, $trait);
        }

        return $result;
    }

    /**
     * Validates that a handler implements CommandHandlerInterface when using command-only traits.
     *
     * Certain traits like ShouldRunAfterResponse and ShouldBeTransactional should only be
     * applied to Command handlers, not Query handlers, because:
     * - Queries must return results synchronously (can't be deferred)
     * - Queries should be read-only (transactions are for write operations)
     *
     * @param  string  $handler_class  The fully qualified handler class name
     * @param  string  $trait  The trait name being checked (for error messages)
     *
     * @throws RuntimeException If the handler doesn't implement CommandHandlerInterface
     */
    private function checkForCommandInterface(string $handler_class, string $trait): void
    {
        if (!is_subclass_of($handler_class, CommandHandlerInterface::class))
        {
            $msg = "Handler [{$handler_class}] implements {$trait}, but does not implement CommandHandlerInterface.";

            throw new RuntimeException($msg);
        }
    }
}
