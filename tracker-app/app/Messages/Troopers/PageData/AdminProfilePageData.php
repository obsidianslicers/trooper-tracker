<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Messages\Changes\Queries\GetModelChangesForTrooper;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForReview;
use App\Messages\Troopers\Queries\GetMostRecentEventsForTrooper;
use App\Messages\Troopers\Queries\Membership\GetModeratedOrganizationIds;
use App\Messages\Troopers\Queries\Membership\GetTrooperAuthorityOrganizations;
use App\Messages\Troopers\Queries\Membership\GetTrooperOrganizationMemberships;
use App\Messages\Troopers\Resources\AdminTrooperDetails;
use App\Messages\Troopers\Resources\ProfileAuthorityResource;
use App\Messages\Troopers\Resources\ProfileGuardianResource;
use App\Messages\Troopers\Resources\ProfileMembershipsResource;
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
            'details' => new AdminTrooperDetails($this->trooper, $this->actor),
            'authority' => $this->getAuthority(),
            'memberships' => $this->getMemberships(),
            'guardian' => $this->getGuardian(),
            'costumes' => $this->getCostumes(),
            'events' => $this->getEvents(),
            'changes' => $this->getChanges(),
        ];
    }

    private function getAuthority(): ?ProfileAuthorityResource
    {
        if (!$this->actor->can('updateAuthority', $this->trooper))
        {
            return null;
        }

        $organizations = GetTrooperAuthorityOrganizations::call(trooper: $this->trooper);

        return new ProfileAuthorityResource(
            trooper: $this->trooper,
            may_give_system_owner: (bool) $this->actor->has_system_owner,
            organizations: new TrooperAuthorityOrganizationCollection($organizations),
        );
    }

    private function getMemberships(): ProfileMembershipsResource
    {
        $organizations = GetTrooperOrganizationMemberships::call(trooper: $this->trooper);
        $allowed_ids = GetModeratedOrganizationIds::call(moderator: $this->actor);

        return new ProfileMembershipsResource(new TrooperOrganizationMembershipCollection(
            $organizations,
            $allowed_ids,
            (bool) $this->trooper->is_visitor,
        ));
    }

    private function getGuardian(): ?ProfileGuardianResource
    {
        if (!$this->trooper->has_guardian_required_membership)
        {
            return null;
        }

        return new ProfileGuardianResource($this->trooper);
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
}
