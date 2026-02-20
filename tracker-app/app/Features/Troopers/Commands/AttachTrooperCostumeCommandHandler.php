<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\TrooperCostume;

/**
 * Handler for attaching a costume to a trooper.
 *
 * Validation:
 * 1. Verifies organization exists and trooper has access (via withActiveTroopers scope)
 * 2. Verifies costume belongs to the specified organization
 * 3. Creates TrooperCostume or restores soft-deleted record if it exists
 *
 * This ensures troopers can only attach costumes from organizations they belong to.
 *
 * @implements CommandHandlerInterface<AttachTrooperCostumeCommand>
 */
readonly class AttachTrooperCostumeCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to attach a costume.
     *
     * @param AttachTrooperCostumeCommand $message The command with trooper, organization, and costume IDs
     * @return null
     */
    public function __invoke(object $message): mixed
    {
        //  all organizations the trooper belongs to
        $organization_costumes = OrganizationCostume::query()
            ->whereIn(OrganizationCostume::ID, $message->organization_ids)
            ->whereHas('organization', function ($query) use ($message)
            {
                $query->withActiveTroopers($message->trooper->id);
            })
            ->get();

        foreach ($organization_costumes as $organization_costume)
        {
            $trooper_costume = $message->trooper->trooper_costumes()
                ->withTrashed()
                ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $organization_costume->id)
                ->first();

            if ($trooper_costume === null)
            {
                $attributes = [TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id];

                $message->trooper->trooper_costumes()->create($attributes);
            }
            else
            {
                $trooper_costume->restore();
            }
        }

        return null;
    }
}

