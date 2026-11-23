<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Memberships tab of a trooper's Command Staff profile (admin/troopers/components/profile/
 * ProfileMemberships): their organizations, and the statuses a membership can be set to.
 *
 * @property TrooperOrganizationMembershipCollection $resource
 */
class ProfileMembershipsResource extends JsonResource
{
    public function __construct(TrooperOrganizationMembershipCollection $organizations)
    {
        parent::__construct($organizations);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status_options' => collect(MembershipStatus::toArray())
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'organizations' => $this->resource,
        ];
    }
}
