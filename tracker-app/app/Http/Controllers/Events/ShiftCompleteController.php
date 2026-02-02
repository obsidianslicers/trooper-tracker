<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\EventTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Updates a trooper's event shift status based on an encrypted link.
 *
 * This controller processes status update links (typically from emails) that
 * allow troopers to update their attendance status by clicking encrypted URLs.
 * The status is decrypted from the URL and applied to the event trooper record.
 */
class ShiftCompleteController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Events', 'events.list');
    }

    /**
     * Handle the incoming request to update a trooper's event status
     *
     * Decrypts the status parameter from the URL, updates the event trooper's
     * status, and displays a confirmation page.
     *
     * @param  Request  $request  The incoming request
     * @param  EventTrooper  $event_trooper  The event trooper assignment to update
     * @param  string  $status  Encrypted status value to apply
     * @return View The status update confirmation page
     */
    public function __invoke(Request $request, EventTrooper $event_trooper, string $status): View
    {
        $trooper_status = EventTrooperStatus::from(Crypt::decryptString($status));

        $valid_data = [
            EventTrooper::STATUS => $trooper_status,
        ];

        $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

        $this->bus->send($event_trooper_cmd);

        return view('events.shift-complete', compact('event_trooper'));
    }
}
