<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event's name, times, venue or comments changed after it was saved.
 */
final class EventDetailsChanged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $changed_fields  The Event column names that changed.
     */
    public function __construct(
        public readonly Event $event,
        public readonly array $changed_fields,
    ) {}
}
