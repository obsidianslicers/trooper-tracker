<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;

/**
 * Command to attach a costume to a trooper.
 *
 * Creates a TrooperCostume record linking the trooper to an OrganizationCostume.
 * Validates that the costume belongs to an organization the trooper has access to.
 * If a soft-deleted TrooperCostume exists, it will be restored instead of creating a new one.
 *
 * @method static void call(Trooper $trooper, array $organization_ids)
 */
final class AttachTrooperCostume extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper to attach the costume to
     * @param  array  $organization_ids  The IDs of the organizations that own the costumes to attach
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $organization_ids
    ) {}

    /**
     * Execute the command to attach a costume.
     *
     * @return null
     */
    public function handle(): void
    {
        //  all organizations the trooper belongs to
        $organization_costumes = OrganizationCostume::query()
            ->whereIn(OrganizationCostume::ID, $this->organization_ids)
            ->whereHas('organization', function ($query)
            {
                $query->withActiveTroopers($this->trooper->id);
            })
            ->get();

        foreach ($organization_costumes as $organization_costume)
        {
            $trooper_costume = $this->trooper->trooper_costumes()
                ->withTrashed()
                ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
                ->first();

            if ($trooper_costume === null)
            {
                $attributes = [TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id];

                $this->trooper->trooper_costumes()->create($attributes);
            }
            else
            {
                $trooper_costume->restore();
            }
        }
    }
}
