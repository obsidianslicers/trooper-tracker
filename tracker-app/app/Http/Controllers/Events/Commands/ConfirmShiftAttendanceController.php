<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\FlashType;
use App\Enums\ShiftAttendanceOutcome;
use App\Http\Requests\Events\ConfirmShiftAttendanceRequest;
use App\Messages\Events\Commands\ConfirmShiftAttendance;
use App\Models\EventTrooper;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Records "Made It" or "Missed It" from the bell or the notifications page,
 * then goes back to the page it came from. A costume that can credit more than one club goes
 * to the club choice page first, the same page the email link opens.
 */
class ConfirmShiftAttendanceController
{
    public function __invoke(ConfirmShiftAttendanceRequest $request, EventTrooper $event_trooper): SymfonyResponse
    {
        $answer = $request->answer();

        $outcome = ConfirmShiftAttendance::call(event_trooper: $event_trooper, status: $answer);

        if ($outcome === ShiftAttendanceOutcome::CHOOSE_CLUB)
        {
            return Inertia::location($event_trooper->getAttendanceUrl($answer));
        }

        $type = $outcome === ShiftAttendanceOutcome::CLOSED ? FlashType::DANGER : FlashType::SUCCESS;
        $type->flash($outcome->message($answer));

        return redirect()->back(fallback: route('account.push-notifications'));
    }
}
