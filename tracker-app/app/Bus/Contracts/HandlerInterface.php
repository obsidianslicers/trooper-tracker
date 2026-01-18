<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

/**
 * Interface for Command and Query handlers.
 *
 * Handlers implementing this interface process Command or Query messages
 * through their __invoke method. This pattern enables single-responsibility
 * handlers that can be automatically discovered and executed by MagicBus.
 *
 * The handler is responsible for:
 * - Validating business rules
 * - Executing domain logic
 * - Interacting with repositories and services
 * - Returning results (for Queries) or performing actions (for Commands)
 *
 * Naming Convention:
 * - Message class: MyTask (Command or Query)
 * - Handler class: MyTaskHandler
 *
 * Example:
 * ```php
 * class GetUserQueryHandler implements HandlerInterface
 * {
 *     public function __invoke(GetUserQuery $query): User
 *     {
 *         return User::findOrFail($query->userId);
 *     }
 * }
 * ```
 *
 * @template TMessage of object
 * @see \App\Bus\MagicBus
 * @see \App\Bus\Contracts\CommandHandlerInterface
 * @see \App\Bus\Contracts\QueryHandlerInterface
 */
interface HandlerInterface
{
    /**
     * Handles the given Command or Query.
     *
     * This method is invoked by MagicBus when the corresponding Command
     * or Query is dispatched. The implementation should contain the core
     * business logic for processing the message.
     *
     * @param TMessage $message The Command or Query message to process
     * @return mixed The result of handling the message (may be void for Commands)
     */
    public function __invoke(object $message): mixed;
}