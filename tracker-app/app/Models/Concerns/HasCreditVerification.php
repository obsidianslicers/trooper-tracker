<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\EventTrooperStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Scopes\ModeratorCoverage;
use App\Models\Trooper;

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
     * Requested clubs this moderator may verify, by the same rule as costume reviews
     * (see ModeratorCoverage). Nobody verifies their own troops.
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

        return Organization::whereIn(Organization::ID, $requested)
            ->where(fn ($query) => ModeratorCoverage::apply(
                $query,
                $moderator,
                (int) $this->{EventTrooper::TROOPER_ID},
                (new Organization)->qualifyColumn(Organization::ID),
            ))
            ->pluck(Organization::ID)
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
