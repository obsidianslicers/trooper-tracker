@extends('layouts.base')

@section('page-title', 'Status Change Log')

@section('content')

    <x-table class="caption-top">
        <caption>
            Troopers who {{ \App\Enums\EventTrooperStatus::ATTENDED->name }} an event where
            their status was updated by someone other than the trooper in action.
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
                        {{ $change->updated_at->format('D - M d, Y g:ia') }}
                    </td>
                    <td>
                        <a href="{{ route('admin.events.update', $change->event_shift->event) }}">
                            {{ $change->event_shift->event->name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->event_shift->time_display }}
                    </td>
                    <td>
                        <a href="{{ route('admin.troopers.changes', $change->trooper) }}">
                            {{ $change->trooper->name }}
                        </a>
                    </td>
                    <td>
                        {{ $change->updated_by->name }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        No status changes found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-table>

@endsection