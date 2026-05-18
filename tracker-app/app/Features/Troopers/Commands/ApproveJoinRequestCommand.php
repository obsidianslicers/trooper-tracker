<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperJoinRequest;

/**
 * Command to approve a pending club join request.
 *
 * Sets the request status to APPROVED, creates a TrooperAssignment,
 * persists any identifier to TrooperOrganization, and notifies the trooper.
 *
 * @see ApproveJoinRequestCommandHandler
 */
readonly class ApproveJoinRequestCommand
{
    /**
     * @param  TrooperJoinRequest  $join_request  The pending join request to approve
     */
    public function __construct(
        public TrooperJoinRequest $join_request,
    ) {}
}
