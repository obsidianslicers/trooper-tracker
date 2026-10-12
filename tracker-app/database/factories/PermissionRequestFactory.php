<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use Database\Factories\Base\PermissionRequestFactory as BasePermissionRequestFactory;

class PermissionRequestFactory extends BasePermissionRequestFactory
{
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            PermissionRequest::REQUESTER_TROOPER_ID => Trooper::factory()->asModerator(),
            PermissionRequest::MEMBERSHIP_ROLE => MembershipRole::MODERATOR,
            PermissionRequest::MODERATED_ORGANIZATION_IDS => [],
            PermissionRequest::FORUM_GROUPS_TO_ADD => [],
            PermissionRequest::FORUM_GROUPS_TO_REMOVE => [],
            PermissionRequest::STATUS => PermissionRequestStatus::PENDING,
        ]);
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function requestedBy(Trooper $trooper): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::REQUESTER_TROOPER_ID => $trooper->id,
        ]);
    }

    public function withRole(?MembershipRole $role, array $organization_ids = []): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::MEMBERSHIP_ROLE => $role,
            PermissionRequest::MODERATED_ORGANIZATION_IDS => $organization_ids,
        ]);
    }

    /**
     * @param  array<int, array{id:int,title:string}>  $add
     * @param  array<int, array{id:int,title:string}>  $remove
     */
    public function withForumGroups(array $add = [], array $remove = []): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::FORUM_GROUPS_TO_ADD => $add,
            PermissionRequest::FORUM_GROUPS_TO_REMOVE => $remove,
        ]);
    }

    public function asApproved(): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::STATUS => PermissionRequestStatus::APPROVED,
            PermissionRequest::REVIEWED_AT => now(),
        ]);
    }

    public function asDenied(?string $reason = null): static
    {
        return $this->state(fn (array $attributes): array => [
            PermissionRequest::STATUS => PermissionRequestStatus::DENIED,
            PermissionRequest::DENIAL_REASON => $reason,
            PermissionRequest::REVIEWED_AT => now(),
        ]);
    }
}
