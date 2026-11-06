<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use App\Notifications\Troopers\DirectlyAddedToClubNotification;
use Hyperdrive\Message;

/**
 * Command to directly add a trooper to an organization's roster.
 *
 * Used by moderators to add troopers to their club without requiring
 * a join request. Creates a TrooperAssignment and, when provided,
 * persists the identifier to TrooperOrganization.
 *
 * @method static void call(Trooper $trooper, Organization $organization, ?string $identifier)
 */
final class DirectAddTrooper extends Message
{
    /**
     * @param  Trooper  $trooper  The trooper being added to the organization
     * @param  Organization  $organization  The leaf-node organization to assign them to
     * @param  string|null  $identifier  Optional club-specific identifier (e.g., TK number)
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
        private readonly ?string $identifier,
    ) {}

    public function handle(): void
    {
        $primary_club = $this->organization->getPrimaryClub();

        // Clear any existing club membership in the same top-level org hierarchy (replace rule).
        TrooperAssignment::where(TrooperAssignment::TROOPER_ID, $this->trooper->id)
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->whereHas('organization', fn ($q) => $q
                ->withinNodePath($primary_club->node_path)
                ->where(Organization::ID, '!=', $this->organization->id)
            )
            ->update([TrooperAssignment::IS_MEMBER => false]);

        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID => $this->trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            ],
            [
                TrooperAssignment::IS_MEMBER => true,
            ]
        );

        $update_data = [TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE];

        if (!empty($this->identifier))
        {
            $update_data[TrooperOrganization::IDENTIFIER] = $this->identifier;
        }

        TrooperOrganization::updateOrCreate(
            [
                TrooperOrganization::TROOPER_ID => $this->trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $primary_club->id,
            ],
            $update_data
        );

        $this->trooper->notify(new DirectlyAddedToClubNotification($this->organization));
    }
}
