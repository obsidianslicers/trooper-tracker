<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Models\Event;
use App\Models\EventShift;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The roster of an event, or of one of its shifts, as CSV rows: who is signed up, and their guests.
 *
 * @method static array{filename: string, rows: array<int, array<int, string>>} call(Event $event, ?EventShift $event_shift = null)
 */
final class RosterCsvPageData extends Message
{
    public const COLUMN_COUNT = 10;

    public const HEADINGS = [
        'Event Date',
        'Event Time',
        'Shift Time',
        'Trooper Name',
        'Guest Name',
        'Costume',
        'Back Up Costume',
        'Station',
        'Status',
        'Added By',
    ];

    public function __construct(
        private readonly Event $event,
        private readonly ?EventShift $event_shift = null,
    ) {}

    /**
     * @return array{filename: string, rows: array<int, array<int, string>>}
     */
    public function handle(): array
    {
        $this->event->load($this->relations());

        $event_shifts = $this->event_shift === null
            ? $this->event->event_shifts
            : $this->event->event_shifts->where('id', $this->event_shift->id)->values();

        $suffix = $this->event_shift === null ? 'all-shifts' : 'shift-'.$this->event_shift->id;

        return [
            'filename' => sprintf('event-roster-%s-%s.csv', str($this->event->name)->slug(), $suffix),
            'rows' => [...$this->titleRows(), self::HEADINGS, ...$this->rosterRows($event_shifts)],
        ];
    }

    /**
     * The event's name above the middle column, then a blank row.
     *
     * @return array<int, array<int, string>>
     */
    private function titleRows(): array
    {
        $title_row = array_fill(0, self::COLUMN_COUNT, '');
        $title_row[(int) floor(self::COLUMN_COUNT / 2)] = $this->event->name;

        return [$title_row, array_fill(0, self::COLUMN_COUNT, '')];
    }

    /**
     * @param  Collection<int, EventShift>  $event_shifts
     * @return array<int, array<int, string>>
     */
    private function rosterRows(Collection $event_shifts): array
    {
        $event_date = $this->event->event_start?->format('Y-m-d') ?? '';
        $event_time = $this->eventTime();
        $rows = [];

        foreach ($event_shifts as $event_shift)
        {
            $when = [$event_date, $event_time, $event_shift->short_time_display];

            foreach ($event_shift->event_troopers as $event_trooper)
            {
                $rows[] = [
                    ...$when,
                    $event_trooper->trooper?->legal_name ?? '',
                    '',
                    $event_trooper->costume?->name ?? '',
                    $event_trooper->backup_costume?->name ?? '',
                    $event_trooper->event_shift_station?->name ?? '',
                    (string) to_title($event_trooper->status->name),
                    $event_trooper->added_by_trooper?->legal_name ?? '',
                ];
            }

            foreach ($event_shift->event_guests as $event_guest)
            {
                $rows[] = [
                    ...$when,
                    '',
                    $event_guest->name,
                    '',
                    '',
                    '',
                    (string) to_title($event_guest->status->name),
                    $event_guest->added_by_trooper?->legal_name ?? '',
                ];
            }
        }

        return $rows;
    }

    private function eventTime(): string
    {
        $start = $this->event->event_start;
        $end = $this->event->event_end;

        return match (true)
        {
            $start !== null && $end !== null => $start->format('g:ia').' - '.$end->format('g:ia'),
            $start !== null => $start->format('g:ia'),
            default => '',
        };
    }

    /**
     * @return array<string|int, mixed>
     */
    private function relations(): array
    {
        return [
            'event_shifts' => fn ($query) => $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc'),
            'event_shifts.event_troopers' => fn ($query) => $query->orderBy('signed_up_at', 'asc'),
            'event_shifts.event_troopers.trooper:id,display_name,legal_name',
            'event_shifts.event_troopers.added_by_trooper:id,display_name,legal_name',
            'event_shifts.event_troopers.costume:id,name',
            'event_shifts.event_troopers.backup_costume:id,name',
            'event_shifts.event_troopers.event_shift_station:id,name',
            'event_shifts.event_guests' => fn ($query) => $query->orderBy('name', 'asc'),
            'event_shifts.event_guests.added_by_trooper:id,display_name,legal_name',
        ];
    }
}
