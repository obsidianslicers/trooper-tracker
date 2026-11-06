<?php

declare(strict_types=1);

namespace Hyperdrive\Events;

use Hyperdrive\Message;

/**
 * Fired after a message is hydrated and before its handle() method runs.
 */
final readonly class MessageHandling
{
    public function __construct(public Message $message) {}
}
