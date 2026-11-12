<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Messages\Changes\Queries\GetModelChangesForTrooper;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForReview;
use App\Messages\Troopers\Queries\GetMostRecentEventsForTrooper;
use App\Messages\Troopers\Queries\Membership\GetTrooperOrganizationMemberships;
use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\ModelChange;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the command staff trooper profile page and all of its tabs.
 *
 * @method static array<string, mixed> call()
 */
final class AdminProfilePageData extends Message
{
    private const int CHANGES_LOOKBACK_DAYS = 30;

    /**
     * @param  Actor&Trooper  $actor  The moderator or administrator viewing the page
     * @param  Trooper  $trooper  The trooper being managed
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly Trooper $trooper,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'trooper_id' => $this->trooper->id,
            'display_name' => $this->trooper->display_name,
            'membership_role' => $this->trooper->membership_role->value,
            'membership_role_label' => to_title($this->trooper->membership_role->name)->toString(),
            'details' => $this->getDetails(),
            'authority' => $this->getAuthority(),
            'memberships' => $this->getMemberships(),
            'guardian' => $this->getGuardian(),
            'costumes' => $this->getCostumes(),
            'events' => $this->getEvents(),
            'changes' => $this->getChanges(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDetails(): array
    {
        $trooper = $this->trooper;

        return [
            'legal_name' => $trooper->legal_name,
            'display_name' => $trooper->display_name,
            'email' => $trooper->email,
            'phone' => $trooper->phone,
            'membership_status' => $trooper->membership_status->value,
            'membership_status_options' => $this->getStatusOptions(),
            'last_active_at' => $trooper->last_active_at?->format('D - M d, Y') ?? 'Never',
            'is_invalid' => $trooper->membership_status === MembershipStatus::INVALID,
            'is_departed' => $trooper->membership_status === MembershipStatus::DEPARTED,
            'is_untrusted' => (bool) $trooper->is_untrusted,
            'untrusted_reason' => $trooper->untrusted_reason,
            'can_mark_untrusted' => $this->actor->can('markUntrusted', $trooper),
            'trooper_stamps' => new TrooperStampsResource($trooper),
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function getStatusOptions(): array
    {
        $choices = MembershipStatus::choicesFor(
            $this->actor->is_administrator,
            $this->trooper->membership_status
        );

        return $this->toOptions($choices);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getAuthority(): ?array
    {
        if (!$this->actor->can('updateAuthority', $this->trooper))
        {
            return null;
        }

        $organizations = Organization::withAllAssignments($this->trooper)->get();

        return [
            'membership_role' => $this->trooper->membership_role->value,
            'role_options' => $this->toOptions($this->getRoleChoices()),
            'organizations' => $organizations->map(fn(Organization $organization): array => [
                'id' => $organization->id,
                'parent_id' => $organization->parent_id,
                'depth' => (int) $organization->depth,
                'name' => $organization->name,
                'is_moderator' => (bool) $organization->trooper_assignments->first()?->is_moderator,
                'should_notify' => (bool) $organization->trooper_assignments->first()?->should_notify,
                'is_member' => (bool) $organization->trooper_assignments->first()?->is_member,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getRoleChoices(): array
    {
        return collect(MembershipRole::cases())
            ->reject(fn(MembershipRole $role): bool => $role === MembershipRole::SYSTEM_OWNER
                && !$this->actor->has_system_owner)
            ->mapWithKeys(fn(MembershipRole $role): array => [
                $role->value => to_title($role->name)->toString(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getMemberships(): array
    {
        $organizations = GetTrooperOrganizationMemberships::call(trooper: $this->trooper);

        $allowed_ids = Organization::ofTypeOrganizations()
            ->moderatedBy($this->actor)
            ->pluck(Organization::ID)
            ->all();

        return [
            'status_options' => $this->toOptions(MembershipStatus::toArray()),
            'organizations' => $organizations->map(
                fn(Organization $organization): array => $this->toMembership($organization, $allowed_ids)
            )->values()->all(),
        ];
    }

    /**
     * @param  array<int, int>  $allowed_ids
     * @return array<string, mixed>
     */
    private function toMembership(Organization $organization, array $allowed_ids): array
    {
        $assignment = $organization->assignment ?? null;

        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'identifier' => $organization->identifier ?? null,
            'membership_status' => $organization->membership_status?->value
                ?? MembershipStatus::ACTIVE->value,
            'assignment_id' => $assignment?->id,
            'is_member' => (bool) $organization->is_member,
            'can_edit' => in_array($organization->id, $allowed_ids, true),
            'assignment_options' => $this->getAssignmentOptions($organization),
        ];
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    private function getAssignmentOptions(Organization $organization): array
    {
        $max_depth = $this->trooper->is_visitor ? (int) $organization->depth : null;

        return Organization::query()
            ->where(Organization::NODE_PATH, 'like', $organization->node_path . '%')
            ->when($max_depth !== null, fn($query) => $query->where(Organization::DEPTH, '<=', $max_depth))
            ->orderBy(Organization::NODE_PATH)
            ->get()
            ->map(fn(Organization $option): array => [
                'value' => $option->id,
                'label' => str_repeat('— ', max(0, (int) $option->depth - (int) $organization->depth))
                    . $option->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getGuardian(): ?array
    {
        if (!$this->trooper->has_guardian_required_membership)
        {
            return null;
        }

        return [
            'date_of_birth' => $this->trooper->date_of_birth?->format('Y-m-d'),
            'guardian_email' => $this->trooper->guardian?->email,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCostumes(): array
    {
        $costumes = GetTrooperCostumesForReview::call(
            trooper: $this->trooper,
            moderator: $this->actor,
        );

        return $costumes->map(function (TrooperCostume $tc): array
        {
            $status = $this->getCostumeStatus($tc);

            return [
                'id' => $tc->id,
                'costume' => $tc->organization_costume?->costume?->name,
                'organization' => $tc->organization_costume?->organization?->name,
                'status' => $status,
                'is_approved' => in_array($status, ['approved', 'club_records'], true),
                'by' => $tc->trashed() && $tc->isRevoked()
                    ? $tc->revoked_by?->display_name
                    : $tc->approved_by?->display_name,
                'can_review' => (bool) $tc->can_review,
            ];
        })->values()->all();
    }

    private function getCostumeStatus(TrooperCostume $trooper_costume): string
    {
        return match (true)
        {
            $trooper_costume->trashed() && $trooper_costume->isRevoked() => 'revoked',
            $trooper_costume->isPending() => 'pending',
            $trooper_costume->synchronized_at !== null => 'club_records',
            default => 'approved',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEvents(): array
    {
        return GetMostRecentEventsForTrooper::call(trooper: $this->trooper)
            ->map(function (Organization $organization): array
            {
                $shift = $organization->event_shift;

                return [
                    'organization' => $organization->name,
                    'event_id' => $shift?->event->id,
                    'event_name' => $shift?->event->name,
                    'shift_date' => $shift?->shift_starts_at->format('M d, Y'),
                    'is_old' => $shift !== null && $shift->shift_starts_at->lt(now()->subYear()),
                ];
            })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getChanges(): array
    {
        $changes = GetModelChangesForTrooper::call(
            trooper: $this->trooper,
            lookback: now()->subDays(self::CHANGES_LOOKBACK_DAYS),
        );

        return $changes->map(fn(ModelChange $change): array => [
            'what' => $change->auditable_type,
            'label' => $change->auditable_label,
            'field' => $change->field_name,
            'old_value' => $change->old_value,
            'new_value' => $change->new_value,
            'when' => $change->created_at->diffForHumans(),
            'who' => $change->trooper->display_name ?? 'System',
        ])->values()->all();
    }

    /**
     * @param  array<string|int, string>  $choices
     * @return array<int, array{value:string|int,label:string}>
     */
    private function toOptions(array $choices): array
    {
        $options = [];

        foreach ($choices as $value => $label)
        {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
