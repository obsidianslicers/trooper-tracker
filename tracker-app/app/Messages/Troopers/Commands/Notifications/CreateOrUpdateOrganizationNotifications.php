<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Notifications;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's organization notifications setting.
 *
 * @method static void call(Trooper $trooper, bool $enabled)
 */
final class CreateOrUpdateOrganizationNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $organization_ids,
        private readonly bool $enabled,
    ) {}

    /**
     * Execute the command to update trooper organization notifications setting.
     *
     * @return null
     */
    public function handle(): void
    {
        foreach ($this->organization_ids as $organization_id)
        {
            CreateOrUpdateOrganizationNotification::call(
                trooper_id: $this->trooper->id,
                organization_id: $organization_id,
                enabled: $this->enabled,
            );
        }
    }
}
