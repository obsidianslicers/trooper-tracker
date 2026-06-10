<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\JoinRequestStatus;
use App\Models\JoinRequest;
use Exception;

/**
 * Handles lifecycle events for the JoinRequest model.
 */
class JoinRequestObserver
{
    /**
     * Handle the JoinRequest "updating" event.
     *
     * Enforces that a status transition can only originate from PENDING.
     * Prevents approving or denying a request that has already been resolved.
     *
     * @throws Exception if the status is being changed and the original status was not pending.
     */
    public function updating(JoinRequest $join_request): void
    {
        if (!$join_request->isDirty(JoinRequest::STATUS))
        {
            return;
        }

        if ($join_request->getRawOriginal(JoinRequest::STATUS) !== JoinRequestStatus::PENDING->value)
        {
            throw new Exception('Join request is not pending.');
        }
    }
}
