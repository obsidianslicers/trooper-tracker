<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Features\Events\Commands\UpdateEventTrooperCommand;
use App\Http\Controllers\MagicBusController;
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
 * The status is decrypted from the URL and applied to the event trooper record.
 */
class ShiftCompleteController extends MagicBusController
{
    private array $attended_messages = [
        'Excellent work trooper! Imperial records now reflect your heroic presence.',
        'Your dedication has been logged in the Imperial archives.',
        'Another successful mission completed. The Empire salutes you.',
        'Your presence was felt across the sector. Well done.',
        'Your armor shines brighter in the records today.',
        'The Emperor himself acknowledges your contribution.',
        "Your actions strengthen the Empire's grip on the galaxy.",
        'Your service brings stability to the Outer Rim.',
        'Your discipline is noted and appreciated.',
        'Your efforts echo through the Imperial halls.',
        'Your loyalty has been recorded with precision.',
        'Your mission performance has exceeded expectations.',
    ];

    private array $absent_messages = [
        'The dark side sensed your effort.',
        'Your signal was faint, trooper. The Empire noticed.',
        'Your armor remained cold this time. Perhaps next mission.',
        'The Emperor felt a disturbance... your absence.',
        'Your squad marched on without you.',
        'Imperial scanners reported no movement from your position.',
        'Your presence was missed on the battlefield.',
        'The mission proceeded, though your post was empty.',
        'Your absence has been logged in the archives.',
        'The Empire awaits your return.',
        'Your squadmates carried the banner without you.',
        'The mission concluded, but your armor remained untouched.',
    ];

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
        try
        {
            $trooper_status = EventTrooperStatus::from(Crypt::decryptString($status));
        }
        catch (DecryptException|ValueError $e)
        {
            abort(404);
        }

        $message = '';

        if ($event_trooper->event_shift->event->can_update_trooper_status)
        {
            if (
                $trooper_status === EventTrooperStatus::ATTENDED
                && $event_trooper->organization_id === null
            ) {
                $parent_organizations = $event_trooper->getEligibleCreditParentOrganizations();

                if ($parent_organizations->count() > 1)
                {
                    return view('pages.events.shift-complete-club-select', [
                        'event_trooper'    => $event_trooper,
                        'organizations'    => $parent_organizations,
                        'encrypted_status' => $status,
                    ]);
                }
            }

            $valid_data = [
                EventTrooper::STATUS => $trooper_status,
            ];

            $event_trooper_cmd = new UpdateEventTrooperCommand($event_trooper, $valid_data);

            $this->bus->send($event_trooper_cmd);

            $message = $this->getMessageForStatus($trooper_status);
        }
        else
        {
            $this->flash->danger('Status updates for this event are no longer permitted. Please reach out to your administrator for further help.');
        }

        return view('pages.events.shift-complete', compact('event_trooper', 'message'));
    }

    private function getMessageForStatus(EventTrooperStatus $status): string
    {
        $day = now()->day;
        $index = ($day - 1) % 12;

        if ($status == EventTrooperStatus::ATTENDED)
        {
            return $this->attended_messages[$index];
        }

        return $this->absent_messages[$index];
    }
}
