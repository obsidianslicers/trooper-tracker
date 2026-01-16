<?php

declare(strict_types=1);

namespace App\Bus;

use App\Bus\Contracts\HandlerInterface;
use Illuminate\Support\Facades\App;
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
 * @see \App\Bus\HandlerInterface
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
     * @param object $message The Command or Query object to dispatch
     * @return mixed The result returned by the handler's __invoke method
     *
     * @throws RuntimeException If the handler class does not exist
     * @throws RuntimeException If the handler does not implement HandlerInterface
     */
    public function send(object $message): mixed
    {
        // Convention: MyTask -> MyTaskHandler
        $handler_class = get_class($message) . 'Handler';

        if (!class_exists($handler_class))
        {
            throw new RuntimeException("Missing Handler: Create the class [{$handler_class}]");
        }

        $handler = App::make($handler_class);

        if (!$handler instanceof HandlerInterface)
        {
            throw new RuntimeException("Handler [{$handler_class}] must implement " . HandlerInterface::class);
        }

        if ($handler instanceof ShouldRunAfterResponse)
        {
            if (!$message instanceof CommandInterface)
            {
                throw new RuntimeException(
                    sprintf(
                        'Handler [%s] implements ShouldRunAfterResponse, but message [%s] does not implement CommandInterface.',
                        $handler_class,
                        get_class($message)
                    )
                );
            }

            // Defer execution until after the HTTP response is sent
            app()->afterResponse(function () use ($handler, $message)
            {
                $handler($message);
            });

            // Deferred commands do not return a value
            return null;
        }

        // Executes the __invoke method in the handler
        return $handler($message);
    }
}