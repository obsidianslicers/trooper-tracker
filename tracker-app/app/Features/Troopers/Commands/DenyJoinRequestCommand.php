<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\JoinRequest;

/**
 * Command to deny a pending club join request.
 *
 * Sets status to DENIED, persists the denial reason on the record, and notifies the trooper.
 *
 * @see DenyJoinRequestCommandHandler
 */
readonly class DenyJoinRequestCommand
{
    /**
     * @param  JoinRequest  $join_request  The pending JoinRequest to deny
     * @param  string|null  $denial_reason Optional reason shown to the trooper and stored on the record
     */
    public function __construct(
        public JoinRequest $join_request,
        public ?string $denial_reason = null,
    ) {}
}
