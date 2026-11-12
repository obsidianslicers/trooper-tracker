<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\Membership\GetAssignableOrganizations;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperOrganizationMembershipCollection extends ResourceCollection
{
    /**
     * @param  mixed  $resource  Organizations with the trooper's membership data loaded
     * @param  array<int, int>  $allowed_ids  Organizations the viewer may edit
     */
    public function __construct(
        $resource,
        private readonly array $allowed_ids,
        private readonly bool $is_visitor,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (Organization $organization): array => [
                'id' => $organization->id,
                'name' => $organization->name,
                'identifier' => $organization->identifier ?? null,
                'membership_status' => $organization->membership_status?->value
                    ?? MembershipStatus::ACTIVE->value,
                'assignment_id' => ($organization->assignment ?? null)?->id,
                'is_member' => (bool) $organization->is_member,
                'can_edit' => in_array($organization->id, $this->allowed_ids, true),
                'assignment_options' => $this->getAssignmentOptions($organization),
            ])->values()->all();
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    private function getAssignmentOptions(Organization $organization): array
    {
        return GetAssignableOrganizations::call(
            organization: $organization,
            is_visitor: $this->is_visitor,
        )->map(fn (Organization $option): array => [
            'value' => $option->id,
            'label' => str_repeat('— ', max(0, (int) $option->depth - (int) $organization->depth))
                .$option->name,
        ])->all();
    }
}
