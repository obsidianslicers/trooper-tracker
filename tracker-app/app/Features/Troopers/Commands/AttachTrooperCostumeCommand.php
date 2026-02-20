<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to attach a costume to a trooper.
 *
 * Creates a TrooperCostume record linking the trooper to an OrganizationCostume.
 * Validates that the costume belongs to an organization the trooper has access to.
 * If a soft-deleted TrooperCostume exists, it will be restored instead of creating a new one.
 *
 * @see AttachTrooperCostumeCommandHandler
 */
readonly class AttachTrooperCostumeCommand
{
    /**
     * Create a new command instance.
     *
     * @param Trooper $trooper The trooper to attach the costume to
     * @param array $organization_ids The IDs of the organizations that own the costumes to attach
     */
    public function __construct(
        public Trooper $trooper,
        public array $organization_ids
    ) {
    }
}

