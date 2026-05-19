<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\TrooperOrganization;

/**
 * Handler for updating a trooper's organization-specific identifiers.
 *
 * Updates identifier values (e.g., TK numbers, TKID) for each organization
 * the trooper belongs to. Updates the tt_trooper_organizations pivot table.
 *
 * @implements CommandHandlerInterface<UpdateTrooperIdentifiersCommand>
 */
readonly class UpdateTrooperIdentifiersCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to update memberships.
     *
     * @param  UpdateTrooperIdentifiersCommand  $message  The command with trooper and membership data
     * @return null
     */
    public function __invoke(object $message): void
    {
        foreach ($message->valid_data as $organization_id => $data)
        {
            $identifier = $data['identifier'] ?? null;

            if ($identifier === null)
            {
                continue;
            }

            $identifier = trim($identifier);

            $trooper_organization = TrooperOrganization::query()
                ->withTrashed()
                ->where(TrooperOrganization::TROOPER_ID, $message->trooper->id)
                ->where(TrooperOrganization::ORGANIZATION_ID, $organization_id)
                ->first();

            if ($trooper_organization)
            {
                if ($trooper_organization->trashed())
                {
                    $trooper_organization->restore();
                }

                $trooper_organization->identifier = $identifier;
                $trooper_organization->save();

                continue;
            }

            $trooper_organization = new TrooperOrganization;
            $trooper_organization->trooper_id = $message->trooper->id;
            $trooper_organization->organization_id = $organization_id;
            $trooper_organization->identifier = $identifier;
            $trooper_organization->save();
        }
    }
}
