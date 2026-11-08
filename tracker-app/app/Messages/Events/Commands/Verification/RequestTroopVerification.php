<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Verification;

use App\Jobs\SendTroopVerificationNotificationsJob;
use App\Models\EventTrooper;
use Hyperdrive\Message;

/**
 * A flagged trooper confirmed a troop: hold the clubs they claimed until moderators verify
 * them, and let those moderators know.
 *
 * @method static void call(EventTrooper $event_trooper, array $organization_ids)
 */
final class RequestTroopVerification extends Message
{
    /**
     * @param  array<int, int>  $organization_ids
     */
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly array $organization_ids,
    ) {}

    public function handle(): void
    {
        $this->event_trooper->requestVerification($this->organization_ids);
        $this->event_trooper->save();

        SendTroopVerificationNotificationsJob::dispatch($this->event_trooper);
    }
}
