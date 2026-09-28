<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\AdministrativeNotifications;
use App\Enums\NotificationChannels;
use App\Enums\NotificationFrequency;
use App\Enums\OrganizationType;
use App\Enums\TrooperNotifications;
use App\Messages\Account\Queries\GetOrganizationNotifications;
use App\Messages\Account\Resources\OrganizationNotificationCollection;
use App\Messages\Account\Resources\TrooperCostumeCollection;
use App\Messages\Account\Resources\TrooperDetails;
use App\Messages\Account\Resources\TrooperFriendCollection;
use App\Messages\Account\Resources\TrooperMembershipCollection;
use App\Messages\Account\Resources\TrooperMinorCollection;
use App\Messages\Account\Resources\TrooperRequestCollection;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationHierarchy;
use App\Messages\Organizations\Resources\OrganizationOptions;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumes;
use App\Messages\Troopers\Queries\GetTrooperFriends;
use App\Messages\Troopers\Queries\GetTrooperMinors;
use App\Messages\Troopers\Queries\Membership\GetTrooperMemberships;
use App\Messages\Troopers\Queries\Membership\GetTrooperRequests;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves data for the Renew Visitor page.
 *
 * This query message responds with data necessary for rendering the Renew Visitor page, including trooper details,
 * notifications, costumes, memberships, friends, and minors.
 * Used by frontend clients to display the renewal interface for visitor troopers.
 *
 * @method static array<string, mixed> call()
 */
final class RenewVisitorPageData extends Message
{
    /**
     * Constructs the RenewVisitorPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        private readonly Actor $actor
    ) {
    }

    /**
     * Retrieves data for the Renew Visitor page as a nested associative array.
     *
     * @return array Data array with trooper details, notifications, costumes, memberships, friends, and minors
     */
    public function handle(): array
    {
        $data = [
            'visitor_expires_at' => $this->actor->visitor_expires_at,
        ];

        return $data;
    }

}
