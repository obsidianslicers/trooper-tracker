<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Verification;

use App\Models\EventTrooper;
use Hyperdrive\Message;
use RuntimeException;

/**
 * A moderator turns down one club's credit for a flagged trooper's troop. When no club is
 * credited or waiting any more, the troop becomes a No Show.
 */
final class RejectTroopCredit extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly int $organization_id,
    ) {}

    public function handle(): void
    {
        $this->assertRequested();

        $this->event_trooper->rejectRequestedOrganization($this->organization_id);
        $this->event_trooper->save();
    }

    private function assertRequested(): void
    {
        $requested = array_map('intval', $this->event_trooper->requested_organization_ids ?? []);

        if (!in_array($this->organization_id, $requested, true))
        {
            throw new RuntimeException('That club is not waiting for verification on this troop.');
        }
    }
}
