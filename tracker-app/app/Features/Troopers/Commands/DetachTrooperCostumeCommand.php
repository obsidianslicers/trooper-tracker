<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to detach (soft-delete) a costume from a trooper.
 *
 * Removes a trooper's costume by soft-deleting the TrooperCostume record.
 * The costume can be re-attached later via AttachTrooperCostumeCommand,
 * which will restore the soft-deleted record.
 *
 * @see DetachTrooperCostumeCommandHandler
 */
readonly class DetachTrooperCostumeCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper to detach the costume from
     * @param  int  $costume_id  The TrooperCostume ID to detach (not OrganizationCostume ID)
     */
    public function __construct(
        public Trooper $trooper,
        public int $costume_id
    ) {}
}
