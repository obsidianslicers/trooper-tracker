<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventShift;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadRosterCsvController extends MagicBusController
{
    public function allShifts(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('update', $event);

        $event->load($this->relations());

        $filename = $this->buildFilename($event, 'all-shifts');

        return $this->streamCsv($event, $event->event_shifts, $filename);
    }

    public function singleShift(Request $request, Event $event, EventShift $event_shift): StreamedResponse
    {
        $this->authorize('update', $event);

        abort_unless((int) $event_shift->event_id === (int) $event->id, 404);

        $event->load($this->relations());

        $eventShift = $event->event_shifts->firstWhere('id', $event_shift->id);
        abort_unless($eventShift !== null, 404);

        $filename = $this->buildFilename($event, 'shift-' . $eventShift->id);

        return $this->streamCsv($event, collect([$eventShift]), $filename);
    }

    private function relations(): array
    {
        return [
            'event_shifts' => function ($query) {
                $query->orderBy(EventShift::SHIFT_STARTS_AT, 'asc');
            },
            'event_shifts.event_troopers' => function ($query) {
                $query->orderBy('signed_up_at', 'asc');
            },
            'event_shifts.event_troopers.trooper:id,display_name',
            'event_shifts.event_troopers.added_by_trooper:id,display_name',
            'event_shifts.event_troopers.costume:id,name',
            'event_shifts.event_troopers.backup_costume:id,name',
            'event_shifts.event_guests' => function ($query) {
                $query->orderBy('name', 'asc');
            },
            'event_shifts.event_guests.added_by_trooper:id,display_name',
        ];
    }

    private function streamCsv(Event $event, $eventShifts, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($event, $eventShifts) {
            $handle = fopen('php://output', 'w');

            $columnCount = 9;
            $titleRow = array_fill(0, $columnCount, '');
            $titleRow[(int) floor($columnCount / 2)] = $event->name;
            fputcsv($handle, $titleRow);
            fputcsv($handle, array_fill(0, $columnCount, ''));

            fputcsv($handle, [
                'Event Date',
                'Event Time',
                'Shift Time',
                'Trooper Name',
                'Guest Name',
                'Costume',
                'Back Up Costume',
                'Status',
                'Added By',
            ]);

            $eventDate = $event->event_start?->format('Y-m-d') ?? '';
            $eventTime = '';
            if ($event->event_start && $event->event_end)
            {
                $eventTime = $event->event_start->format('g:ia') . ' - ' . $event->event_end->format('g:ia');
            }
            elseif ($event->event_start)
            {
                $eventTime = $event->event_start->format('g:ia');
            }

            foreach ($eventShifts as $eventShift)
            {
                foreach ($eventShift->event_troopers as $eventTrooper)
                {
                    fputcsv($handle, [
                        $eventDate,
                        $eventTime,
                        $eventShift->short_time_display,
                        $eventTrooper->trooper?->display_name ?? '',
                        '',
                        $eventTrooper->costume?->name ?? '',
                        $eventTrooper->backup_costume?->name ?? '',
                        to_title($eventTrooper->status->name),
                        $eventTrooper->added_by_trooper?->display_name ?? '',
                    ]);
                }

                foreach ($eventShift->event_guests as $eventGuest)
                {
                    fputcsv($handle, [
                        $eventDate,
                        $eventTime,
                        $eventShift->short_time_display,
                        '',
                        $eventGuest->name,
                        '',
                        '',
                        to_title($eventGuest->status->name),
                        $eventGuest->added_by_trooper?->display_name ?? '',
                    ]);
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildFilename(Event $event, string $suffix): string
    {
        $slug = str($event->name)->slug();

        return sprintf('event-roster-%s-%s.csv', $slug, $suffix);
    }
}
