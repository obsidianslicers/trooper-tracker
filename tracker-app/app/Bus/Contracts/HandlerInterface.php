<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

/**
 * Interface for Command and Query handlers.
 *
 * Handlers implementing this interface process Command or Query objects
 * through their __invoke method. This pattern enables single-responsibility
 * handlers that can be automatically discovered and executed by MagicBus.
 *
 * Naming Convention:
 * - Command/Query class: MyTask
 * - Handler class: MyTaskHandler
 *
 * @template TMessage of object
 * @see \App\Bus\MagicBus
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