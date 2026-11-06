<?php

declare(strict_types=1);

namespace Hyperdrive\Events;

use Hyperdrive\Message;

/**
 * Fired after a message's handle() method returns.
 *
 * For transactional messages it fires once the transaction has committed.
 */
final readonly class MessageHandled
{
    public function __construct(public Message $message, public mixed $result) {}
}
