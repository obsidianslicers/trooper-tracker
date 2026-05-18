<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperJoinRequest;

/**
 * Command to deny a pending club join request.
 *
 * Sets the request status to DENIED and notifies the trooper.
 *
 * @see DenyJoinRequestCommandHandler
 */
readonly class DenyJoinRequestCommand
{
    /**
     * @param  TrooperJoinRequest  $join_request  The pending join request to deny
     */
    public function __construct(
        public TrooperJoinRequest $join_request,
    ) {}
}
