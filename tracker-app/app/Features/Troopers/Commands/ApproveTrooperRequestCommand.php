<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperRequest;

/**
 * Command to approve a pending club join request.
 *
 * Creates TrooperOrganization at the primary club, creates TrooperAssignment at the
 * requested organization, marks the join request approved, and notifies the trooper.
 *
 * @see ApproveTrooperRequestCommandHandler
 */
readonly class ApproveTrooperRequestCommand
{
    /**
     * @param  TrooperRequest  $trooper_request  The pending TrooperRequest to approve
     * @param  bool  $suppress_notification  When true, skips the approval notification
     */
    public function __construct(
        public TrooperRequest $trooper_request,
        public bool $suppress_notification = false,
    ) {}
}
