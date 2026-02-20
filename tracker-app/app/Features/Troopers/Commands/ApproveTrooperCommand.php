<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to approve or deny a pending trooper registration.
 *
 * Updates a trooper's membership status from PENDING to ACTIVE (if approved)
 * or sets denial timestamp. Approved troopers gain full access to the system.
 *
 * @see ApproveTrooperCommandHandler
 */
readonly class ApproveTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper to approve or deny
     * @param  bool  $is_approved  Whether the trooper is approved (true) or denied (false)
     */
    public function __construct(
        public Trooper $trooper,
        public bool $is_approved,
    ) {}
}
