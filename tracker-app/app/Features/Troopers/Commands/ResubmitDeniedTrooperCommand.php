<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Organization;
use App\Models\Trooper;

/**
 * Command to resubmit a denied trooper's registration application.
 *
 * Resets the trooper's status to pending, restores their denied club
 * requests to pending, and optionally creates a new club request.
 *
 * @see ResubmitDeniedTrooperCommandHandler
 */
readonly class ResubmitDeniedTrooperCommand
{
    public function __construct(
        public Trooper $trooper,
        public ?Organization $organization = null,
        public ?string $identifier = null,
    ) {}
}
