<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\ReconcileEventRosterJob;
use App\Jobs\SendEventCancelledNotificationsJob;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Saves the event edit form: details, organizations, then the roster and notifications that follow.
 *
 * Opening a draft opens its shifts and tells troopers about the event; cancelling tells the roster.
 *
 * @method static void call(Event $event, array $data, Trooper $updated_by_trooper)
 */
final class UpdateEvent extends Message
{
    /**
     * @param  array<string, mixed>  $data  The validated event form
     */
    public function __construct(
        private readonly Event $event,
        private readonly array $data,
        private readonly Trooper $updated_by_trooper,
    ) {}

    public function handle(): void
    {
        $current_status = $this->event->status;
        $updated_status = EventStatus::from($this->data['status']);

        UpdateEventDetails::call(event: $this->event, data: $this->data);
        UpdateEventOrganizations::call(event: $this->event, data: $this->data['organizations'] ?? []);

        dispatch(new ReconcileEventRosterJob($this->event, $this->updated_by_trooper));

        if ($current_status === $updated_status)
        {
            return;
        }

        if ($current_status === EventStatus::DRAFT && $this->opensSignUps($updated_status))
        {
            $this->openShiftsAfterResponse($updated_status);

            dispatch(new SendEventCreatedNotificationsJob($this->event));
        }
        elseif ($current_status !== EventStatus::DRAFT && $updated_status === EventStatus::CANCELLED)
        {
            dispatch(new SendEventCancelledNotificationsJob($this->event));
        }
    }

    private function opensSignUps(EventStatus $status): bool
    {
        return in_array(
            $status,
            [EventStatus::OPEN, EventStatus::MANUAL_SELECTION, EventStatus::SIGN_UP_LOCKED],
            true,
        );
    }

    private function openShiftsAfterResponse(EventStatus $status): void
    {
        $event = $this->event;

        dispatch(function () use ($event, $status)
        {
            foreach ($event->event_shifts as $shift)
            {
                $shift->status = $status;
                $shift->save();
            }
        })->afterResponse();
    }
}
