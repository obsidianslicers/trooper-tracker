<?php

declare(strict_types=1);

namespace App\Models\Observers;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use Exception;

/**
 * Handles lifecycle events for the TrooperRequest model.
 */
class TrooperRequestObserver
{
    /**
     * Handle the TrooperRequest "updating" event.
     *
     * Enforces that a status transition can only originate from PENDING.
     * Prevents approving or denying a request that has already been resolved.
     *
     * @throws Exception if the status is being changed and the original status was not pending.
     */
    public function updating(TrooperRequest $trooper_request): void
    {
        if (!$trooper_request->isDirty(TrooperRequest::STATUS))
        {
            return;
        }

        if ($trooper_request->getRawOriginal(TrooperRequest::STATUS) !== TrooperRequestStatus::PENDING->value)
        {
            throw new Exception('Join request is not pending.');
        }
    }
}
