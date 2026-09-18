<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\MembershipStatus;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;

/**
 * Activates a trooper's membership in an organization and its primary organization.
 *
 * The organization assignment is created or updated as an active member. The primary
 * organization membership is created or updated as active, restored when soft-deleted,
 * and assigned the supplied identifier. Empty identifiers are stored as null.
 *
 * @method static void call(int $trooper_id, int $primary_organization_id, int $organization_id, string|null $identifier)
 */
final class CreateOrUpdateOrganizationMembership extends Message
{
    public function __construct(
        private readonly int $trooper_id,
        private readonly int $primary_organization_id,
        private readonly int $organization_id,
        private readonly string|null $identifier,
    ) {
    }

    /**
     * Execute the command to update trooper organization membership setting.
     *
     * @return null
     */
    public function handle(): void
    {
        ClearOrganizationAssignments::call(
            trooper_id: $this->trooper_id,
            primary_organization: $this->primary_organization_id,
        );

        CreateOrUpdateOrganizationAssignment::call(
            trooper_id: $this->trooper_id,
            organization_id: $this->organization_id,
            is_member: true,
        );

        $trooper_organization = TrooperOrganization::query()
            ->withTrashed()
            ->where(TrooperOrganization::TROOPER_ID, $this->trooper_id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $this->primary_organization_id)
            ->first();

        if ($trooper_organization === null)
        {
            $trooper_organization = new TrooperOrganization();
            $trooper_organization->trooper_id = $this->trooper_id;
            $trooper_organization->organization_id = $this->primary_organization_id;
        }

        $trooper_organization->membership_status = MembershipStatus::ACTIVE;

        if (empty($this->identifier))
        {
            $trooper_organization->identifier = null;
        }
        else
        {
            $trooper_organization->identifier = $this->identifier;
        }

        if ($trooper_organization->trashed())
        {
            $trooper_organization->restore();
        }

        $trooper_organization->save();
    }
}
