<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Verification;

use App\Messages\Events\Commands\Corrections\SettleTroopCorrection;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use RuntimeException;

/**
 * A moderator confirms one club's credit for a flagged trooper's troop. That club counts it
 * from now on; any other requested clubs keep waiting.
 */
final class ApproveTroopCredit extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly int $organization_id,
        private readonly ?Trooper $moderator = null,
    ) {}

    public function handle(): void
    {
        $this->assertRequested();

        $this->event_trooper->approveRequestedOrganization($this->organization_id);
        $this->event_trooper->save();

        SettleTroopCorrection::call(event_trooper: $this->event_trooper, moderator: $this->moderator);
    }

    private function assertRequested(): void
    {
        $requested = array_map('intval', $this->event_trooper->requested_organization_ids ?? []);

        if (!in_array($this->organization_id, $requested, true))
        {
            throw new RuntimeException(__('events.That club is not waiting for verification on this troop.'));
        }
    }
}
