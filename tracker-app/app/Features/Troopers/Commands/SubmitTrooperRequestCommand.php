<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;

/**
 * Command to submit a club join request on behalf of a trooper.
 *
 * Creates a pending TrooperRequest record for later approval.
 * Moderators of the target organization are notified by the handler.
 *
 * @see SubmitTrooperRequestCommandHandler
 */
readonly class SubmitTrooperRequestCommand
{
    /**
     * @param  Trooper  $trooper  The trooper requesting membership
     * @param  Organization  $organization  The leaf-node organization to join
     * @param  string|null  $identifier  Optional club-specific identifier (e.g., TK number)
     */
    public function __construct(
        public Trooper $trooper,
        public Organization $organization,
        public ?string $identifier,
    ) {}
}
