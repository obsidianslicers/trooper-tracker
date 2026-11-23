<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Models\Event;
use Hyperdrive\Message;
use Spatie\CalendarLinks\Generators\Ics;

/**
 * The event as a calendar file.
 *
 * @method static array{filename: string, content: string} call(Event $event)
 */
final class EventIcsPageData extends Message
{
    public function __construct(private readonly Event $event) {}

    /**
     * @return array{filename: string, content: string}
     */
    public function handle(): array
    {
        $generator = new Ics([], ['format' => 'file']);

        return [
            'filename' => str($this->event->name)->slug().'.ics',
            'content' => $generator->generate($this->event->createCalendarLink()),
        ];
    }
}
