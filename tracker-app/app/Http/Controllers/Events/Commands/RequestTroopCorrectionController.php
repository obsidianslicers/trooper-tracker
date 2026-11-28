<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\FlashType;
use App\Enums\TroopCorrectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Commands\RequestTroopCorrectionRequest;
use App\Messages\Events\Commands\Corrections\RequestTroopCorrection;
use Illuminate\Http\RedirectResponse;

/**
 * A trooper asks for credit for a completed event they never signed up for, then goes to the
 * event page, where their sign-up now waits for verification.
 */
class RequestTroopCorrectionController extends Controller
{
    public function __invoke(RequestTroopCorrectionRequest $request): RedirectResponse
    {
        $event = $request->event();

        $correction = RequestTroopCorrection::call(
            trooper: $request->user(),
            event: $event,
            details: $request->validated('details'),
            organization_ids: array_map('intval', $request->validated('organization_ids') ?? []),
            event_shift: $request->eventShift(),
            costume_id: $request->validated('costume_id') ? (int) $request->validated('costume_id') : null,
            proof_url: $request->validated('proof_url'),
        );

        FlashType::success($correction->status === TroopCorrectionStatus::AWAITING_COORDINATOR
            ? __('events.Request sent. The event coordinator will confirm it, and you will hear back.')
            : __('events.Request sent. A moderator will check it, and you will hear back.'));

        return redirect()->route('events.display', $event);
    }
}
