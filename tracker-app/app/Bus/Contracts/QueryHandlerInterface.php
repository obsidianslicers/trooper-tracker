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
 * @see \App\Bus\MagicBus
 */
interface QueryHandlerInterface extends HandlerInterface
{
}