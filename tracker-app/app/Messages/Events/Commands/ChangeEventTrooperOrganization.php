<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\SignUpChangeOutcome;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Changes which organization a trooper troops for on a shift. Their costumes are cleared, since
 * they depend on the organization, and a going trooper can't move to an organization that's full.
 *
 * @method static SignUpChangeOutcome call(EventTrooper $event_trooper, ?int $organization_id, Trooper $changed_by_trooper)
 */
final class ChangeEventTrooperOrganization extends Message
{
    public function __construct(
        private readonly EventTrooper $event_trooper,
        private readonly ?int $organization_id,
        private readonly Trooper $changed_by_trooper,
    ) {}

    public function handle(): SignUpChangeOutcome
    {
        $event_trooper = $this->event_trooper;

        if (!$event_trooper->canUpdateCostume($this->changed_by_trooper))
        {
            return SignUpChangeOutcome::FORBIDDEN;
        }

        if (
            $this->organization_id !== null
            && $event_trooper->intendsToGo()
            && $event_trooper->event_shift->orgTroopersMaxed($this->organization_id, $event_trooper->is_handler)
        ) {
            return SignUpChangeOutcome::ORGANIZATION_FULL;
        }

        UpdateEventTrooper::call(event_trooper: $event_trooper, valid_data: [
            EventTrooper::ORGANIZATION_ID => $this->organization_id,
            EventTrooper::COSTUME_ID => null,
            EventTrooper::BACKUP_COSTUME_ID => null,
        ]);

        return SignUpChangeOutcome::SAVED;
    }
}
