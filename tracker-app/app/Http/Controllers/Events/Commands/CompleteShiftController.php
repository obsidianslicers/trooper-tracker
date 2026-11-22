<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Commands;

use App\Enums\EventTrooperStatus;
use App\Enums\FlashType;
use App\Enums\ShiftAttendanceOutcome;
use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\ConfirmShiftAttendance;
use App\Models\EventTrooper;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use ValueError;

/**
 * Updates a trooper's event shift status based on an encrypted link.
 *
 * This controller processes status update links (typically from emails) that
 * allow troopers to update their attendance status by clicking encrypted URLs.
 * The status is decrypted from the URL and handed to ConfirmShiftAttendance.
 */
class CompleteShiftController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('events.Events'), 'events.list');
    }

    /**
     * Handle the incoming request to update a trooper's event status
     *
     * @param  Request  $request  The incoming request
     * @param  EventTrooper  $event_trooper  The event trooper assignment to update
     * @param  string  $status  Encrypted status value to apply
     * @return View The status update confirmation page, or the club choice
     */
    public function __invoke(Request $request, EventTrooper $event_trooper, string $status): View
    {
        try
        {
            $trooper_status = EventTrooperStatus::from(Crypt::decryptString($status));
        }
        catch (DecryptException|ValueError $e)
        {
            abort(404);
        }

        $outcome = ConfirmShiftAttendance::call(event_trooper: $event_trooper, status: $trooper_status);

        if ($outcome === ShiftAttendanceOutcome::CHOOSE_CLUB)
        {
            return view('pages.events.shift-complete-club-select', [
                'event_trooper' => $event_trooper,
                'organizations' => $event_trooper->getEligibleCreditParentOrganizations(),
                'encrypted_status' => $status,
            ]);
        }

        $message = $outcome->message($trooper_status);

        if ($outcome === ShiftAttendanceOutcome::CLOSED)
        {
            FlashType::danger($message);
            $message = '';
        }

        return view('pages.events.shift-complete', compact('event_trooper', 'message'));
    }
}
