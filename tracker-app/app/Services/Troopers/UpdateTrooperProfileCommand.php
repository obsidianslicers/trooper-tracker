<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\Trooper;

/**
 * Command to update a trooper's profile information.
 *
 * Updates the trooper's email, notification frequency, and marks the setup
 * as completed by setting the setup_completed_at timestamp.
 *
 * @package App\Services\Troopers
 */
class UpdateTrooperProfileCommand
{
    /**
     * Update the trooper's profile information.
     *
     * Updates the trooper's email and notification frequency preferences,
     * and sets the setup_completed_at timestamp to mark the setup as complete.
     *
     * @param Trooper $trooper The trooper whose profile to update.
     * @param array $data The validated data containing email and notification_frequency.
     * @return void
     */
    public function __invoke(Trooper $trooper, array $data): void
    {
        $trooper->email = $data['email'];
        $trooper->notification_frequency = $data['notification_frequency'];
        $trooper->setup_completed_at = now();

        $trooper->save();
    }
}
