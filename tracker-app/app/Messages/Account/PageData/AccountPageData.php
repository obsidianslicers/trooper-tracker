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
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array<string, mixed> call()
 */
final class AccountPageData extends Message
{
    /**
     * Constructs the AccountPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        public readonly Actor $actor
    ) {}

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $data = [
            'trooper_id' => $this->actor->id,
            'is_visitor' => $this->actor->is_visitor,
            'is_handler' => $this->actor->is_handler,
            'deletion_requested_at' => $this->actor->deletion_requested_at,
            'email' => $this->actor->email,
            'details' => $this->getDetails(),
            'notifications' => $this->getNotifications(),
            'costumes' => $this->getCostumes(),
            'memberships' => $this->getMemberships(),
            'friends' => $this->getFriends(),
            'minors' => $this->getMinors(),
        ];

        return $data;
    }

    private function getDetails(): TrooperDetails
    {
        return $this->actor->toResource(TrooperDetails::class);
    }

    private function getNotifications(): array
    {
        return [
            'is_administrator' => $this->actor->is_administrator,
            'trooper_notification_enums' => TrooperNotifications::toValueLabels(),
            'administrative_notification_enums' => AdministrativeNotifications::toValueLabels(),
            'notification_frequency_enums' => NotificationFrequency::toValueLabels(),
            'notification_frequency' => $this->actor->notification_frequency,
            'push_notifications_enabled' => $this->actor->push_notifications_enabled,
            'notification_preferences' => $this->actor->notification_preferences ?? $this->defaultNotificationPreferences(),
            'organization_notifications' => $this->getOrganizationNotifications(),
        ];
    }

    private function defaultNotificationPreferences(): array
    {
        $channels = array_keys(NotificationChannels::toArray());

        $preferences = [];

        foreach (TrooperNotifications::toArray() as $key => $label)
        {
            $preferences[$key] = array_fill_keys($channels, true);
        }

        foreach (AdministrativeNotifications::toArray() as $key => $label)
        {
            $preferences[$key] = array_fill_keys($channels, true);
        }

        return $preferences;
    }

    private function getOrganizationNotifications(): OrganizationNotificationCollection
    {
        $collection = GetOrganizationNotifications::call(trooper: $this->actor);

        return new OrganizationNotificationCollection($collection);
    }

    private function getFriends(): TrooperFriendCollection
    {
        $collection = GetTrooperFriends::call(trooper: $this->actor);

        return new TrooperFriendCollection($collection);
    }

    private function getMinors(): TrooperMinorCollection
    {
        $collection = GetTrooperMinors::call(trooper: $this->actor);

        return new TrooperMinorCollection($collection);
    }

    private function getCostumes(): TrooperCostumeCollection
    {
        $collection = GetTrooperCostumes::call(trooper: $this->actor);

        return new TrooperCostumeCollection($collection);
    }

    private function getMemberships(): array
    {
        return [
            'organizations' => $this->getOrganizationHierarchy(),
            'organization_options' => $this->getOrganizationOptions(),
            'organization_memberships' => $this->getOrganizationMemberships(),
            'organization_requests' => $this->getTrooperRequests(),
        ];
    }

    private function getOrganizationMemberships(): TrooperMembershipCollection
    {
        $collection = GetTrooperMemberships::call(trooper: $this->actor);

        return new TrooperMembershipCollection($collection);
    }

    private function getTrooperRequests(): TrooperRequestCollection
    {
        $collection = GetTrooperRequests::call(trooper: $this->actor);

        return new TrooperRequestCollection($collection);
    }

    private function getOrganizationHierarchy(): OrganizationHierarchy
    {
        $collection = GetOrganizationHierarchy::call();

        return new OrganizationHierarchy($collection);
    }

    private function getOrganizationOptions(): OrganizationOptions
    {
        $collection = GetOrganizationHierarchy::call();

        $organization_type = OrganizationType::UNIT;

        if ($this->actor->is_visitor)
        {
            $organization_type = OrganizationType::ORGANIZATION;
        }

        return new OrganizationOptions($collection, $organization_type);
    }
}
