<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event has ended and been closed by tracker:close-events.
 */
final class EventClosed implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Event $event) {}
}
