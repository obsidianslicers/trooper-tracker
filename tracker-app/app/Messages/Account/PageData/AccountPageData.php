<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\NotificationChannels;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperNotifications;
use App\Enums\AdministrativeNotifications;
use App\Messages\Account\Resources\OrganizationNotificationCollection;
use App\Messages\Account\Resources\TrooperDetails;
use App\Messages\Account\Queries\GetOrganizationNotifications;
use App\Messages\Account\Resources\TrooperMembershipCollection;
use App\Messages\Account\Resources\TrooperMinorCollection;
use App\Messages\Account\Resources\TrooperFriendCollection;
use App\Messages\Account\Resources\TrooperCostumeCollection;
use App\Messages\Troopers\Queries\GetTrooperCostumes;
use App\Messages\Troopers\Queries\GetTrooperMemberships;
use App\Messages\Troopers\Queries\GetTrooperMinors;
use App\Models\Trooper;
use Hyperdrive\Message;
use Hyperdrive\Contracts\Actor;
use App\Messages\Troopers\Queries\GetTrooperFriends;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
     * @param Actor&Trooper $actor The actor representing the current user
     */
    public function __construct(
        public readonly Actor $actor
    ) {
    }

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $data = [
            'trooper_id' => $this->actor->id,
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
            'organization_memberships' => $this->getOrganizationMemberships(),
            'denied' => ['data' => []]
        ];
    }

    private function getOrganizationMemberships(): TrooperMembershipCollection
    {
        $collection = GetTrooperMemberships::call(trooper: $this->actor);

        return new TrooperMembershipCollection($collection);
    }
}
