<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperRequest;

/**
 * Command to deny a pending club join request.
 *
 * Sets status to DENIED, persists the denial reason on the record, and notifies the trooper.
 *
 * @see DenyTrooperRequestCommandHandler
 */
readonly class DenyTrooperRequestCommand
{
    /**
     * @param  TrooperRequest  $trooper_request  The pending TrooperRequest to deny
     * @param  string|null  $denial_reason  Optional reason shown to the trooper and stored on the record
     */
    public function __construct(
        public TrooperRequest $trooper_request,
        public ?string $denial_reason = null,
        public bool $suppress_notification = false,
    ) {}
}
