<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The account page's Notifications tab (account/components/Notifications): the categories this
 * trooper can turn on or off with their current settings, how often they get event mail, push
 * alerts, and the clubs they follow for new events.
 *
 * @mixin Trooper
 */
class AccountNotificationsResource extends JsonResource
{
    /**
     * @param  array<int, array{value: string, label: string}>  $trooper_options
     * @param  array<int, array{value: string, label: string}>  $administrative_options
     * @param  array<string, array<string, bool>>  $preferences  every category, defaults filled in
     */
    public function __construct(
        Trooper $trooper,
        private readonly array $trooper_options,
        private readonly array $administrative_options,
        private readonly array $preferences,
        private readonly OrganizationNotificationCollection $organization_notifications,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trooper_notification_enums' => $this->trooper_options,
            'administrative_notification_enums' => $this->administrative_options,
            'notification_frequency_enums' => NotificationFrequency::toValueLabels(),
            Trooper::NOTIFICATION_FREQUENCY => $this->notification_frequency,
            Trooper::PUSH_NOTIFICATIONS_ENABLED => $this->push_notifications_enabled,
            'notification_preferences' => $this->offeredPreferences(),
            'organization_notifications' => $this->organization_notifications,
        ];
    }

    /**
     * Only the categories the page shows: a trooper who isn't command staff has no
     * administrative categories, so their settings for those aren't sent.
     *
     * @return array<string, array<string, bool>>
     */
    private function offeredPreferences(): array
    {
        $offered = array_column([...$this->trooper_options, ...$this->administrative_options], 'value');

        return array_intersect_key($this->preferences, array_flip($offered));
    }
}
