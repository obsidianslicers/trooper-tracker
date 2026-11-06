<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;

/**
 * Troop credit a flagged ("do not trust") trooper claimed, waiting for moderators.
 *
 * The clubs the trooper asked for sit in requested_organization_ids. Each club is
 * approved on its own: an approved club moves into costume_organization_ids and the
 * row becomes Attended, so that club's reports count it while the others still wait.
 *
 * @mixin EventTrooper
 */
trait HasCreditVerification
{
    public function isAwaitingVerification(): bool
    {
        return !empty($this->{EventTrooper::REQUESTED_ORGANIZATION_IDS});
    }

    /**
     * Park the claimed credit until moderators verify it.
     *
     * @param  array<int, int>  $organization_ids
     */
    public function requestVerification(array $organization_ids): void
    {
        $this->{EventTrooper::STATUS} = EventTrooperStatus::AWAITING_VERIFICATION;
        $this->{EventTrooper::REQUESTED_ORGANIZATION_IDS} = array_values(array_unique(array_map('intval', $organization_ids)));
        $this->{EventTrooper::COSTUME_ORGANIZATION_IDS} = null;
        $this->{EventTrooper::ORGANIZATION_ID} = null;
        $this->{EventTrooper::VERIFICATION_REQUESTED_AT} = now();
    }

    public function approveRequestedOrganization(int $organization_id): void
    {
        $credited = $this->{EventTrooper::COSTUME_ORGANIZATION_IDS} ?? [];
        $credited[] = $organization_id;

        $this->{EventTrooper::COSTUME_ORGANIZATION_IDS} = array_values(array_unique($credited));
        $this->{EventTrooper::STATUS} = EventTrooperStatus::ATTENDED;
        $this->removeRequestedOrganization($organization_id);
    }

    public function rejectRequestedOrganization(int $organization_id): void
    {
        $this->removeRequestedOrganization($organization_id);

        if (!$this->isAwaitingVerification() && empty($this->{EventTrooper::COSTUME_ORGANIZATION_IDS}))
        {
            $this->{EventTrooper::STATUS} = EventTrooperStatus::NO_SHOW;
        }
    }

    /**
     * Requested clubs this moderator may verify: a unit they moderate must cover one of
     * the trooper's memberships and sit in the same club tree as the requested club.
     * Nobody verifies their own troops.
     *
     * @return array<int, int>
     */
    public function verifiableOrganizationIdsFor(Trooper $moderator): array
    {
        $requested = $this->{EventTrooper::REQUESTED_ORGANIZATION_IDS} ?? [];

        if (empty($requested) || $moderator->id === $this->{EventTrooper::TROOPER_ID})
        {
            return [];
        }

        if ($moderator->is_administrator)
        {
            return array_values($requested);
        }

        $moderated_paths = $this->moderatedPathsCoveringTrooper($moderator);

        return Organization::whereIn(Organization::ID, $requested)
            ->get()
            ->filter(fn (Organization $org) => collect($moderated_paths)->contains(
                fn (string $path) => str_starts_with($org->node_path, $path) || str_starts_with($path, $org->node_path)
            ))
            ->pluck(Organization::ID)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function moderatedPathsCoveringTrooper(Trooper $moderator): array
    {
        $member_paths = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $this->{EventTrooper::TROOPER_ID})
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->with('organization')
            ->get()
            ->map(fn (TrooperAssignment $ta) => $ta->organization?->node_path)
            ->filter();

        return TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $moderator->id)
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->with('organization')
            ->get()
            ->map(fn (TrooperAssignment $ta) => $ta->organization?->node_path)
            ->filter(fn (?string $path) => $path !== null
                && $member_paths->contains(fn (string $member) => str_starts_with($member, $path)))
            ->values()
            ->all();
    }

    private function removeRequestedOrganization(int $organization_id): void
    {
        $remaining = array_values(array_filter(
            $this->{EventTrooper::REQUESTED_ORGANIZATION_IDS} ?? [],
            fn ($id) => (int) $id !== $organization_id,
        ));

        $this->{EventTrooper::REQUESTED_ORGANIZATION_IDS} = empty($remaining) ? null : $remaining;
    }
}
