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
                        <a href="{{ route('admin.troopers.profile', compact('trooper_event')) }}">
                            {{ $trooper_event->name }}
                        </a>
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$trooper_event->unique_event_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$trooper_event->total_event_attendance" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        There are not any troopers with closed events in the last {{ $lookback }} days.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">Total</th>
                <th class="text-end">
                    <x-number-format :value="$trooper_events->sum('unique_event_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$trooper_events->sum('total_event_attendance')" />
                </th>
            </tr>
        </tfoot>

    </x-table>

@endsection