<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to update a trooper's organization identifiers.
 *
 * Updates identifier values (e.g., TK numbers, TKID) in the tt_trooper_organizations
 * pivot table for organizations the trooper belongs to. Does not modify
 * TrooperAssignment records or is_member flags.
 *
 * @see UpdateTrooperIdentifiersCommandHandler
 */
readonly class UpdateTrooperIdentifiersCommand
{
    /**
     * Create a new command instance.
     *
     * @param Trooper $trooper The trooper whose identifiers to update
     * @param array<int, array{identifier: string|null}> $valid_data Organization IDs mapped to identifier data
     */
    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
    ) {
    }
}

