<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

/**
 * Interface for Command handlers.
 *
 * Handlers implementing this interface process Command objects
 * through their __invoke method. This pattern enables single-responsibility
 * handlers that can be automatically discovered and executed by MagicBus.
 *
 * Naming Convention:
 * - Command class: MyTask
 * - Handler class: MyTaskHandler
 *
 * @see \App\Bus\MagicBus
 */
interface CommandHandlerInterface extends HandlerInterface
{
}