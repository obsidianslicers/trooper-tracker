<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Queries;

use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Counts the records that reference a costume, including soft-deleted ones.
 *
 * @method static array{organizations: int, troopers: int, event_signups: int} call(Costume $costume)
 */
final class GetCostumeUsage extends Message
{
    public function __construct(private readonly Costume $costume) {}

    /**
     * @return array{organizations: int, troopers: int, event_signups: int}
     */
    public function handle(): array
    {
        $organization_costume_ids = OrganizationCostume::query()
            ->withTrashed()
            ->where(OrganizationCostume::COSTUME_ID, $this->costume->id)
            ->pluck(OrganizationCostume::ID);

        return [
            'organizations' => $organization_costume_ids->count(),
            'troopers' => TrooperCostume::query()
                ->withTrashed()
                ->whereIn(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume_ids)
                ->count(),
            'event_signups' => EventTrooper::query()
                ->withTrashed()
                ->where(fn ($q) => $q
                    ->where(EventTrooper::COSTUME_ID, $this->costume->id)
                    ->orWhere(EventTrooper::BACKUP_COSTUME_ID, $this->costume->id))
                ->count(),
        ];
    }
}
