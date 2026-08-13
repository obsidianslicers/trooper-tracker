<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\NotificationChannels;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperNotifications;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperTheme;
use App\Messages\Account\Queries\GetCostumesWithPrefixes;
use App\Messages\Account\Queries\GetOrganizationNotifications;
use App\Messages\Costumes\Queries\GetCostumes;
use App\Messages\Troopers\Queries\GetTrooperCostumes;
use App\Messages\Troopers\Queries\GetTrooperMinors;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Hyperdrive\Message;
use Hyperdrive\Contracts\Actor;
use App\Messages\Troopers\Queries\GetTrooperFriends;

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

    private function getDetails(): array
    {
        return [
            Trooper::LEGAL_NAME => $this->actor->legal_name,
            Trooper::DISPLAY_NAME => $this->actor->display_name,
            Trooper::PHONE => $this->actor->phone,
            Trooper::THEME => $this->actor->theme,
            Trooper::DISPLAY_COSTUME_ID => $this->actor->display_costume_id,
            'display_costumes' => $this->getDisplayCostumes(),
            'theme_enums' => TrooperTheme::toValueLabels(),
        ];
    }

    private function getDisplayCostumes(): array
    {
        $this->actor->loadMissing('organizations');

        $trooper_costumes = GetCostumesWithPrefixes::call(trooper: $this->actor);

        return $trooper_costumes
            ->mapWithKeys(function (TrooperCostume $tc)
            {
                $organization_costume = $tc->organization_costume;
                $trooper_organization = $this->actor->organizations->firstWhere('id', $organization_costume->organization_id);

                $identifier = ($organization_costume->prefix ?? '') . ($trooper_organization?->pivot?->identifier ?? '');
                $costume_name = $organization_costume->costume?->name ?? '';
                $organization_name = $organization_costume->organization?->name ?? '';

                $label = "$identifier — $costume_name ($organization_name)";

                return [$tc->id => $label];
            })
            ->toArray();
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

    private function getOrganizationNotifications(): array
    {
        return GetOrganizationNotifications::call(trooper: $this->actor)
            ->map(fn(Organization $org) => [
                'id' => $org->id,
                'name' => $org->name,
                'enabled' => $org->enabled,
                'regions' => $org->organizations->map(fn($region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'enabled' => $region->enabled,
                    'units' => $region->organizations->map(fn($unit) => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'enabled' => $unit->enabled,
                    ]),
                ]),
            ])->toArray();
    }

    private function getFriends(): array
    {
        return GetTrooperFriends::call(trooper: $this->actor)
            ->map(fn(Trooper $friend) => [
                Trooper::ID => $friend->id,
                Trooper::LEGAL_NAME => $friend->legal_name,
                Trooper::DISPLAY_NAME => $friend->display_name,
            ])->toArray();
    }

    private function getMinors(): array
    {
        return GetTrooperMinors::call(trooper: $this->actor)
            ->map(fn(Trooper $minor) => [
                Trooper::ID => $minor->id,
                Trooper::LEGAL_NAME => $minor->legal_name,
                Trooper::DISPLAY_NAME => $minor->display_name,
                Trooper::DATE_OF_BIRTH => $minor->date_of_birth,
            ])->toArray();
    }

    private function getCostumes(): array
    {
        $trooper_costumes = GetTrooperCostumes::call(trooper: $this->actor)
            ->map(fn(Costume $costume) => [
                Costume::ID => $costume->id,
                Costume::NAME => $costume->name,
                'costume_organizations' => $costume->costume_organizations ?? '',
            ])->toArray();

        return [
            'trooper_costumes' => $trooper_costumes,
        ];
    }

    private function getMemberships(): array
    {
        return [];
    }
}
