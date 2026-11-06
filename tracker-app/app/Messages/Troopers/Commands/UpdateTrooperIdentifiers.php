<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;

/**
 * Command to update a trooper's organization identifiers.
 *
 * Updates identifier values (e.g., TK numbers, TKID) in the tt_trooper_organizations
 * pivot table for organizations the trooper belongs to. Does not modify
 * TrooperAssignment records or is_member flags.
 *
 * @method static void call(Trooper $trooper, array $valid_data)
 */
final class UpdateTrooperIdentifiers extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper whose identifiers to update
     * @param  array<int, array{identifier?: string|null, assignment?: int|string|null, membership_status?: string|null}>  $valid_data  Organization IDs mapped to membership data
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $valid_data,
    ) {}

    /**
     * Execute the command to update memberships.
     *
     * @return null
     */
    public function handle(): void
    {
        foreach ($this->valid_data as $organization_id => $data)
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
                ->where(TrooperOrganization::TROOPER_ID, $this->trooper->id)
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
            $trooper_organization->trooper_id = $this->trooper->id;
            $trooper_organization->organization_id = $organization_id;
            $trooper_organization->identifier = $identifier;
            $trooper_organization->membership_status = $membership_status;
            $trooper_organization->save();
        }
    }
}
