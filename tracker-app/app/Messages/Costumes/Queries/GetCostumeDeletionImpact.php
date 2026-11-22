<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Queries;

use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * What deleting a costume would change: sign-ups reset to N/A and costumes taken off profiles.
 *
 * @method static array{event_troopers: int, trooper_costumes: int} call(Costume $costume)
 */
final class GetCostumeDeletionImpact extends Message
{
    public function __construct(private readonly Costume $costume) {}

    /**
     * @return array{event_troopers: int, trooper_costumes: int}
     */
    public function handle(): array
    {
        $organization_costume_ids = OrganizationCostume::query()
            ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
            ->pluck(OrganizationCostume::ID);

        return [
            'event_troopers' => EventTrooper::query()
                ->where(EventTrooper::COSTUME_ID, $this->costume->id)
                ->orWhere(EventTrooper::BACKUP_COSTUME_ID, $this->costume->id)
                ->count(),
            'trooper_costumes' => TrooperCostume::query()
                ->withPending()
                ->whereIn(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume_ids)
                ->count(),
        ];
    }
}
