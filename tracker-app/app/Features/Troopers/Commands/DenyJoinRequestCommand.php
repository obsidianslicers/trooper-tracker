<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\TrooperOrganization;

/**
 * Command to deny a pending club join request.
 *
 * Sets the membership status to DENIED and notifies the trooper.
 *
 * @see DenyJoinRequestCommandHandler
 */
readonly class DenyJoinRequestCommand
{
    /**
     * @param  TrooperOrganization  $trooper_organization  The pending TrooperOrganization to deny
     */
    public function __construct(
        public TrooperOrganization $trooper_organization,
        public ?string $denial_reason = null,
    ) {}
}
