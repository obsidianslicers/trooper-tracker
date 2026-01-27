@extends('layouts.base')

@section('page-title', 'Event Type Counts')

@section('content')

    <x-table class="caption-top">
        <caption>
            Count of Event Types over the last {{ $lookback }} days.
        </caption>
        <thead>
            <tr>
                <th scope="col">Event Type</th>
                <th scope="col"
                    class="text-end">
                    Event Count
                </th>
                <th scope="col"
                    class="text-end">
                    Unique Trooper Count
                </th>
                <th scope="col"
                    class="text-end">
                    Total Trooper Attendance
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($event_types as $event_type)
                <tr>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.events.list', ['type' => $event_type->event_type]) }}">
                            {{ to_title($event_type->event_type->name)}}
                        </a>
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event_type->count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event_type->unique_trooper_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event_type->total_trooper_count" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        There are not any events that have been closed in the last {{ $lookback }} days.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">Total</th>
                <th class="text-end">
                    <x-number-format :value="$event_types->sum('count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$event_types->sum('unique_trooper_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$event_types->sum('total_trooper_count')" />
                </th>
            </tr>
        </tfoot>

    </x-table>

@endsection