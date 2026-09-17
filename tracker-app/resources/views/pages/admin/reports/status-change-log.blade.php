@extends('layouts.base')

@section('page-title', 'Status Change Log')

@section('content')

    <x-table class="caption-top">
        <caption>
            Troopers who {{ \App\Enums\EventTrooperStatus::ATTENDED->name }} an event where
            their status was updated by someone other than the trooper in action for
            the last {{ $lookback }} days.
        </caption>
        <thead>
            <tr>
                <th scope="col">Date/Time</th>
                <th scope="col">Event</th>
                <th scope="col">Shift</th>
                <th scope="col">Trooper</th>
                <th scope="col">Updated By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($changes as $change)
                <tr>
                    <td class="text-nowrap">
                        {{ $change->created_at->format('D - M d, Y g:ia') }}
                    </td>
                    <td>
                        <a href="{{ route('admin.events.update', $change->auditable->event_shift->event) }}">
                            {{ $change->auditable->event_shift->event->name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->auditable->event_shift->time_display }}
                    </td>
                    <td>
                        <a href="{{ route('admin.troopers.changes', $change->auditable->trooper) }}">
                            {{ $change->auditable->trooper->display_name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->trooper->display_name ?? 'System' }}
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="5">
                    No status changes found that were updated by anyone but the trooper.
                </x-table-empty>
            @endforelse
        </tbody>
    </x-table>

@endsection