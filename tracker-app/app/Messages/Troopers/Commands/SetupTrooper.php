<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for setting up a trooper's profile information from a TT1.x to TT2.x migration scenario.
 *
 * @method static void call(Trooper $trooper, string $legal_name, TrooperTheme $theme, string $email, NotificationFrequency $notification_frequency)
 */
final class SetupTrooper extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly string $email,
        private readonly string $legal_name,
        private readonly TrooperTheme $theme,
        private readonly NotificationFrequency $notification_frequency,
    ) {
    }

    /**
     * Execute the command to set up trooper profile.
     */
    public function handle(): void
    {
        $this->trooper->email = $this->email;
        $this->trooper->legal_name = $this->legal_name;
        $this->trooper->theme = $this->theme;
        $this->trooper->notification_frequency = $this->notification_frequency;
        $this->trooper->setup_completed_at = now();

        $this->trooper->save();
    }
}
