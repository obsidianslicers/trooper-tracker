<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Command message for adding a costume to a trooper's profile.
 *
 * @method static void call(Trooper $trooper, int $costume_id)
 */
final class AddCostumeToTrooper extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly int $costume_id,
        private readonly array $organization_ids
    ) {}

    /**
     * Execute the command to add a costume to the trooper's profile.
     *
     * @return null
     */
    public function handle(): void
    {
        //  all organizations the trooper belongs to
        $organization_costumes = OrganizationCostume::query()
            ->whereIn(OrganizationCostume::ORGANIZATION_ID, $this->organization_ids)
            ->where(OrganizationCostume::COSTUME_ID, $this->costume_id)
            ->whereHas('organization', function ($query)
            {
                $query->withActiveTroopers($this->trooper->id);
            })
            ->get();

        foreach ($organization_costumes as $organization_costume)
        {
            $this->requestCostume($organization_costume);
        }
    }

    /**
     * Self-added costumes wait for an officer of that club. A costume the trooper removed
     * themselves comes back as it was; one an officer revoked was reset to pending.
     */
    private function requestCostume(OrganizationCostume $organization_costume): void
    {
        $trooper_costume = TrooperCostume::withTrashed()
            ->withPending()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
            ->first();

        if ($trooper_costume === null)
        {
            $this->trooper->trooper_costumes()->create([
                TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
                TrooperCostume::APPROVED_AT => null,
            ]);

            return;
        }

        if ($trooper_costume->trashed())
        {
            $trooper_costume->restore();
        }
    }
}
