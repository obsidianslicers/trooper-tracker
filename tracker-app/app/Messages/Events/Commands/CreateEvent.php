<?php

declare(strict_types=1);

namespace App\Messages\Events\Commands;

use App\Enums\EventStatus;
use App\Jobs\SendEventCreatedNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * Creates an event with one shift covering its whole time and the clubs that can sign up.
 * An event created open for sign-ups tells troopers about it.
 *
 * @method static Event call(array $data)
 */
final class CreateEvent extends Message
{
    use ShouldBeTransactional;

    private const ANNOUNCED = [
        EventStatus::OPEN,
        EventStatus::MANUAL_SELECTION,
        EventStatus::SIGN_UP_LOCKED,
    ];

    /**
     * @param  array<string, mixed>  $data  The validated form, organizations included
     */
    public function __construct(private readonly array $data) {}

    public function handle(): Event
    {
        $event = new Event;
        $event->organization_id = (int) $this->data['organization_id'];

        UpdateEventDetails::call(event: $event, data: $this->data);

        $event_shift = new EventShift;
        $event_shift->event_id = $event->id;
        $event_shift->shift_starts_at = $event->event_start;
        $event_shift->shift_ends_at = $event->event_end;
        $event_shift->status = $event->status;
        $event_shift->save();

        UpdateEventOrganizations::call(event: $event, data: $this->data['organizations'] ?? []);

        if (in_array($event->status, self::ANNOUNCED, true))
        {
            dispatch(new SendEventCreatedNotificationsJob($event))->afterCommit();
        }

        return $event;
    }
}
