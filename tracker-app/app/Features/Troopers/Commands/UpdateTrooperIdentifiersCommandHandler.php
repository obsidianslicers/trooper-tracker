<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
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
    public function __invoke(object $message): mixed
    {
        foreach ($message->valid_data as $organization_id => $data)
        {
            $identifier = array_key_exists('identifier', $data)
                ? trim((string) $data['identifier'])
                : null;
            $identifier = $identifier === '' ? null : $identifier;

            $assignment_id = $data['assignment'] ?? null;
            $assignment_id = $assignment_id === '' ? null : $assignment_id;
            $membership_status = MembershipStatus::tryFrom((string) ($data['membership_status'] ?? ''))
                ?? MembershipStatus::ACTIVE;

            $trooper_organization = TrooperOrganization::query()
                ->withTrashed()
                ->where(TrooperOrganization::TROOPER_ID, $message->trooper->id)
                ->where(TrooperOrganization::ORGANIZATION_ID, $organization_id)
                ->first();

            if ($trooper_organization === null && $identifier === null && $assignment_id === null)
            {
                continue;
            }

            if ($trooper_organization)
            {
                if ($trooper_organization->trashed())
                {
                    $trooper_organization->restore();
                }

                $trooper_organization->identifier = $identifier;
                $trooper_organization->membership_status = $membership_status;
                $trooper_organization->save();

                continue;
            }

            $trooper_organization = new TrooperOrganization;
            $trooper_organization->trooper_id = $message->trooper->id;
            $trooper_organization->organization_id = $organization_id;
            $trooper_organization->identifier = $identifier;
            $trooper_organization->membership_status = $membership_status;
            $trooper_organization->save();
        }

        return null;
    }
}
