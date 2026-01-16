<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

/**
 * Interface for Command and Query messages.
 *
 * Messages implementing this interface are processed by their corresponding handlers
 * through the MagicBus service. This pattern enables single-responsibility
 * messages that can be automatically discovered and executed by MagicBus.
 *
 * Naming Convention:
 * - Command/Query class: MyTask
 * - Handler class: MyTaskHandler
 *
 * @see \App\Bus\MagicBus
 */
interface MessageInterface
{
}