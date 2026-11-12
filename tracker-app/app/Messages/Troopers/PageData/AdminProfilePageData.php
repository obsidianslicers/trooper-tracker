<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Messages\Changes\Queries\GetModelChangesForTrooper;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForReview;
use App\Messages\Troopers\Queries\GetMostRecentEventsForTrooper;
use App\Messages\Troopers\Queries\Membership\GetModeratedOrganizationIds;
use App\Messages\Troopers\Queries\Membership\GetTrooperAuthorityOrganizations;
use App\Messages\Troopers\Queries\Membership\GetTrooperOrganizationMemberships;
use App\Messages\Troopers\Resources\AdminTrooperDetails;
use App\Messages\Troopers\Resources\TrooperAuthorityOrganizationCollection;
use App\Messages\Troopers\Resources\TrooperChangeCollection;
use App\Messages\Troopers\Resources\TrooperCostumeReviewCollection;
use App\Messages\Troopers\Resources\TrooperOrganizationMembershipCollection;
use App\Messages\Troopers\Resources\TrooperRecentEventCollection;
use App\Models\Trooper;
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
    ) {}

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
            'details' => new AdminTrooperDetails($this->trooper, $this->actor),
            'authority' => $this->getAuthority(),
            'memberships' => $this->getMemberships(),
            'guardian' => $this->getGuardian(),
            'costumes' => $this->getCostumes(),
            'events' => $this->getEvents(),
            'changes' => $this->getChanges(),
        ];
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

        $organizations = GetTrooperAuthorityOrganizations::call(trooper: $this->trooper);

        return [
            'membership_role' => $this->trooper->membership_role->value,
            'role_options' => $this->toOptions($this->getRoleChoices()),
            'organizations' => new TrooperAuthorityOrganizationCollection($organizations),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getRoleChoices(): array
    {
        return collect(MembershipRole::cases())
            ->reject(fn (MembershipRole $role): bool => $role === MembershipRole::SYSTEM_OWNER
                && !$this->actor->has_system_owner)
            ->mapWithKeys(fn (MembershipRole $role): array => [
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
        $allowed_ids = GetModeratedOrganizationIds::call(moderator: $this->actor);

        return [
            'status_options' => $this->toOptions(MembershipStatus::toArray()),
            'organizations' => new TrooperOrganizationMembershipCollection(
                $organizations,
                $allowed_ids,
                (bool) $this->trooper->is_visitor,
            ),
        ];
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

    private function getCostumes(): TrooperCostumeReviewCollection
    {
        $collection = GetTrooperCostumesForReview::call(
            trooper: $this->trooper,
            moderator: $this->actor,
        );

        return new TrooperCostumeReviewCollection($collection);
    }

    private function getEvents(): TrooperRecentEventCollection
    {
        $collection = GetMostRecentEventsForTrooper::call(trooper: $this->trooper);

        return new TrooperRecentEventCollection($collection);
    }

    private function getChanges(): TrooperChangeCollection
    {
        $collection = GetModelChangesForTrooper::call(
            trooper: $this->trooper,
            lookback: now()->subDays(self::CHANGES_LOOKBACK_DAYS),
        );

        return new TrooperChangeCollection($collection);
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
