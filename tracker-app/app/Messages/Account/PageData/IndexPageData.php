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
use App\Plugins\PluginNotifications;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (Google or
 * plugin login providers, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 *
 * @method static array<string, mixed> call()
 */
final class IndexPageData extends Message
{
    /**
     * Constructs the IndexPageData message.
     *
     * @param  Actor&Trooper  $actor  The actor representing the current user
     */
    public function __construct(
        private readonly Actor $actor
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
            'visitor_expires_at' => $this->actor->visitor_expires_at,
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
            'trooper_notification_enums' => $this->withPluginCategories(
                TrooperNotifications::toValueLabels(),
                $this->plugins()->trooperCategories(),
            ),
            'administrative_notification_enums' => $this->getAdministrativeNotificationEnums(),
            'notification_frequency_enums' => NotificationFrequency::toValueLabels(),
            'notification_frequency' => $this->actor->notification_frequency,
            'push_notifications_enabled' => $this->actor->push_notifications_enabled,
            'notification_preferences' => $this->getNotificationPreferences(),
            'organization_notifications' => $this->getOrganizationNotifications(),
        ];
    }

    /**
     * Moderators receive most administrative notifications for troopers in their scope,
     * so they get those preferences too, without the administrator-only categories.
     *
     * @return array<int, array{value:string,label:string}>
     */
    private function getAdministrativeNotificationEnums(): array
    {
        if ($this->actor->is_administrator)
        {
            return $this->withPluginCategories(
                AdministrativeNotifications::toValueLabels(),
                $this->plugins()->commandStaffCategories(),
            );
        }

        if ($this->actor->is_moderator)
        {
            return $this->withPluginCategories(
                AdministrativeNotifications::toValueLabels(
                    exclude: AdministrativeNotifications::administratorOnly()
                ),
                $this->plugins()->commandStaffCategories(include_administrator_only: false),
            );
        }

        return [];
    }

    /**
     * @param  array<int, array{value:string,label:string}>  $core
     * @param  array<string, string>  $plugin_categories
     * @return array<int, array{value:string,label:string}>
     */
    private function withPluginCategories(array $core, array $plugin_categories): array
    {
        foreach ($plugin_categories as $value => $label)
        {
            $core[] = ['value' => $value, 'label' => $label];
        }

        usort($core, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $core;
    }

    private function plugins(): PluginNotifications
    {
        return app(PluginNotifications::class);
    }

    /**
     * Stored preferences are sparse (only toggled cells are saved), so overlay them on the
     * defaults to give the UI a value for every category/channel pair.
     */
    private function getNotificationPreferences(): array
    {
        return array_replace_recursive(
            $this->defaultNotificationPreferences(),
            $this->actor->notification_preferences ?? []
        );
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

        foreach ($this->plugins()->values() as $key)
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
