<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to update a trooper's organization notification preferences.
 *
 * Updates TrooperAssignment records to enable/disable notifications (should_notify flag)
 * for specific organizations. Resets all existing preferences to false before
 * applying the new selections.
 *
 * @see UpdateTrooperNotificationsCommandHandler
 */
readonly class UpdateTrooperNotificationsCommand
{
    /**
     * Create a new command instance.
     *
     * @param Trooper $trooper The trooper whose notification preferences to update
     * @param array<int, array{should_notify: bool}> $valid_data Organization IDs mapped to notification settings
     */
    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
    ) {
    }
}

