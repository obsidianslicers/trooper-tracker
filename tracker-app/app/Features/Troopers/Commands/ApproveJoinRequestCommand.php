<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperOrganization;

/**
 * Command to approve a pending club join request.
 *
 * Sets the membership status to ACTIVE, creates a TrooperAssignment,
 * persists any identifier to the primary club TrooperOrganization, and notifies the trooper.
 *
 * @see ApproveJoinRequestCommandHandler
 */
readonly class ApproveJoinRequestCommand
{
    /**
     * @param  TrooperOrganization  $trooper_organization  The pending TrooperOrganization to approve
     * @param  bool  $suppress_notification  When true, skips the JoinRequestApprovedNotification (used for auto-approval during trooper registration approval)
     */
    public function __construct(
        public TrooperOrganization $trooper_organization,
        public bool $suppress_notification = false,
    ) {}
}
