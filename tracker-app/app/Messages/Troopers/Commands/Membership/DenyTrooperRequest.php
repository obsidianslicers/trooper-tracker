<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperRequestDeniedNotification;
use Hyperdrive\Message;

/**
 * Handler for denying a trooper's membership request.
 *
 * @method static void call(TrooperRequest $this->trooper_request, string|null $denial_reason = null, bool $suppress_notification = false)
 */
final class DenyTrooperRequest extends Message
{
    public function __construct(
        private readonly TrooperRequest $trooper_request,
        private readonly string|null $denial_reason = null,
        private readonly bool $suppress_notification = false,
    ) {
    }

    public function handle(): void
    {
        $this->trooper_request->status = TrooperRequestStatus::DENIED;
        $this->trooper_request->denial_reason = $this->denial_reason;
        $this->trooper_request->save();

        if (!$this->suppress_notification)
        {
            $this->trooper_request->trooper->notify(new TrooperRequestDeniedNotification($this->trooper_request));
        }
    }
}
