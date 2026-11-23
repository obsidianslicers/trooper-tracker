<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Models\Event;
use App\Models\EventShift;
use Hyperdrive\Message;
use Spatie\CalendarLinks\Generators\Ics;

/**
 * One shift of an event as a calendar file.
 *
 * @method static array{filename: string, content: string} call(Event $event, EventShift $event_shift)
 */
final class EventShiftIcsPageData extends Message
{
    public function __construct(
        private readonly Event $event,
        private readonly EventShift $event_shift,
    ) {}

    /**
     * @return array{filename: string, content: string}
     */
    public function handle(): array
    {
        $generator = new Ics([], ['format' => 'file']);

        return [
            'filename' => str($this->event->name)->slug().'-shift-'.$this->event_shift->id.'.ics',
            'content' => $generator->generate($this->event_shift->createCalendarLink()),
        ];
    }
}
