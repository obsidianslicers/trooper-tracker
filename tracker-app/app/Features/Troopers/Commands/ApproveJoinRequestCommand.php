<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\JoinRequest;

/**
 * Command to approve a pending club join request.
 *
 * Creates TrooperOrganization at the primary club, creates TrooperAssignment at the
 * requested organization, marks the join request approved, and notifies the trooper.
 *
 * @see ApproveJoinRequestCommandHandler
 */
readonly class ApproveJoinRequestCommand
{
    /**
     * @param  JoinRequest  $join_request  The pending JoinRequest to approve
     * @param  bool  $suppress_notification  When true, skips the approval notification
     */
    public function __construct(
        public JoinRequest $join_request,
        public bool $suppress_notification = false,
    ) {}
}
