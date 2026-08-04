<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\TrooperTheme;
use App\Messages\Account\Queries\GetCostumesWithPrefixes;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Hyperdrive\Message;
use Hyperdrive\Contracts\Actor;

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
            'details' => $this->getAccountDetails(),
            'notifications' => $this->getNotifications()
        ];

        return $data;
    }

    private function getAccountDetails(): array
    {
        return [
            Trooper::LEGAL_NAME => $this->actor->legal_name,
            Trooper::DISPLAY_NAME => $this->actor->display_name,
            Trooper::PHONE => $this->actor->phone,
            Trooper::THEME => $this->actor->theme,
            Trooper::DISPLAY_COSTUME_ID => $this->actor->display_costume_id,
            'display_costumes' => $this->getDisplayCostumes(),
            'themes' => TrooperTheme::toValueLabels(),
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
            'trooper_notifications' => $this->actor->notification_preferences ?? [],
            'notification_frequency' => $this->actor->notification_frequency,
            'push_notifications_enabled' => $this->actor->push_notifications_enabled,
        ];
    }
}
