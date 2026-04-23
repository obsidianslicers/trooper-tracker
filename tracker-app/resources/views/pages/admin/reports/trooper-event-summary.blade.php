@extends('layouts.base')

@section('page-title', 'Trooper Event Counts')

@section('content')

    <x-table class="caption-top">
        <caption>
            Count of Trooper Events over the last {{ $lookback }} days.
        </caption>
        <thead>
            <tr>
                <th>
                    Trooper
                </th>
                <th scope="col"
                    class="text-end">
                    Unique Event Count
                </th>
                <th scope="col"
                    class="text-end">
                    Total Shifts Attendance
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trooper_events as $trooper_event)
                <tr>
                    <td>
                        <a href="{{ route('admin.troopers.profile', ['trooper' => $trooper_event]) }}">
                            {{ $trooper_event->display_name }}
                        </a>
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$trooper_event->events_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$trooper_event->event_shifts_count" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="3">
                    There are not any troopers with closed events in the last {{ $lookback }} days.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">Total</th>
                <th class="text-end">
                    <x-number-format :value="$trooper_events->sum('events_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$trooper_events->sum('event_shifts_count')" />
                </th>
            </tr>
        </tfoot>

    </x-table>

@endsection