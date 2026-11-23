<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Models\EventShare;
use Hyperdrive\Message;

/**
 * Counts one more view of a shared roster.
 *
 * @method static void call(EventShare $event_share)
 */
final class RecordEventShareView extends Message
{
    public function __construct(private readonly EventShare $event_share) {}

    public function handle(): void
    {
        $this->event_share->increment('view_count');
    }
}
