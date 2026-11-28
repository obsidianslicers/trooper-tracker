<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands\Corrections;

use App\Enums\TroopCorrectionStatus;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use App\Notifications\Events\TroopCorrectionDecidedNotification;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use RuntimeException;

/**
 * The event coordinator confirms a trooper was at their event: every club the trooper asked
 * for gets the credit, and the trooper is told.
 *
 * @method static void call(TroopCorrection $troop_correction, Trooper $coordinator)
 */
final class ConfirmTroopCorrection extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly TroopCorrection $troop_correction,
        private readonly Trooper $coordinator,
    ) {}

    public function handle(): void
    {
        $correction = $this->troop_correction;

        if (!$correction->isAwaitingCoordinatorFrom($this->coordinator))
        {
            throw new RuntimeException('This troop credit request is not waiting for this coordinator.');
        }

        $event_trooper = $correction->event_trooper;

        foreach ($event_trooper->requested_organization_ids ?? [] as $organization_id)
        {
            $event_trooper->approveRequestedOrganization((int) $organization_id);
        }

        $event_trooper->save();

        $correction->status = TroopCorrectionStatus::APPROVED;
        $correction->decided_by_trooper_id = $this->coordinator->id;
        $correction->decided_at = now();
        $correction->save();

        $correction->trooper->notify(new TroopCorrectionDecidedNotification($correction));
    }
}
