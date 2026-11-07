<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Jobs\SendCostumeRequestNotificationsJob;
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

        $pending_ids = [];

        foreach ($organization_costumes as $organization_costume)
        {
            $trooper_costume = $this->requestCostume($organization_costume);

            if ($trooper_costume?->isPending())
            {
                $pending_ids[] = $trooper_costume->id;
            }
        }

        if ($pending_ids !== [])
        {
            SendCostumeRequestNotificationsJob::dispatch($this->trooper, $pending_ids);
        }
    }

    /**
     * Self-added costumes wait for an officer of that club. A costume the trooper removed
     * themselves comes back as it was; one an officer revoked was reset to pending.
     * Returns the row only when this call created or restored it, so a costume that
     * is already listed doesn't notify officers again.
     */
    private function requestCostume(OrganizationCostume $organization_costume): ?TrooperCostume
    {
        $trooper_costume = TrooperCostume::withTrashed()
            ->withPending()
            ->where(TrooperCostume::TROOPER_ID, $this->trooper->id)
            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
            ->first();

        if ($trooper_costume === null)
        {
            return $this->trooper->trooper_costumes()->create([
                TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
                TrooperCostume::APPROVED_AT => null,
            ]);
        }

        if (!$trooper_costume->trashed())
        {
            return null;
        }

        $trooper_costume->restore();

        return $trooper_costume;
    }
}
