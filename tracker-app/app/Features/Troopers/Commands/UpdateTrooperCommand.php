<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to update trooper profile information.
 *
 * Updates the trooper model with validated data and optionally marks
 * the trooper's initial setup as completed by setting setup_completed_at.
 *
 * @see UpdateTrooperCommandHandler
 */
readonly class UpdateTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param Trooper $trooper The trooper to update
     * @param array<string, mixed> $valid_data Validated attributes to update on the trooper
     * @param bool $complete_setup Whether to mark setup as completed (default: false)
     */
    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
        public bool $complete_setup = false)
    {
    }
}

