<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands;

use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Deletes a costume, takes it off trooper profiles and resets sign-ups that used it to N/A.
 *
 * @method static void call(Costume $costume)
 */
final class DeleteCostume extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly Costume $costume) {}

    public function handle(): void
    {
        $organization_costume_ids = OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
            ->pluck(OrganizationCostume::ID);

        TrooperCostume::query()
            ->withPending()
            ->whereIn(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume_ids)
            ->get()
            ->each->delete();

        OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
            ->get()
            ->each->delete();

        // A bulk update skips the observer, so costume_organization_ids (the troop credit
        // already earned) stays as it was.
        EventTrooper::query()
            ->where(EventTrooper::COSTUME_ID, $this->costume->id)
            ->update([EventTrooper::COSTUME_ID => null]);

        EventTrooper::query()
            ->where(EventTrooper::BACKUP_COSTUME_ID, $this->costume->id)
            ->update([EventTrooper::BACKUP_COSTUME_ID => null]);

        $this->costume->delete();
    }
}
