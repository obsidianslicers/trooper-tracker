@extends('layouts.base')

@section('page-title', 'Event Volunteer Counts')

@section('content')

    <x-table class="caption-top">
        <caption>
            Summary of Event Details over the last {{ $lookback }} days.
        </caption>
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th></th>
                <th></th>
                <th scope="col"
                    class="text-nowrap text-center"
                    colspan="2">
                    -- Troopers --
                </th>
                <th scope="col"
                    class="text-nowrap text-center"
                    colspan="2">
                    -- Donations --
                </th>
            </tr>
            <tr>
                <th style="width: 36px;"></th>
                <th>
                    Name
                </th>
                <th>
                    Shifts
                </th>
                <th scope="col"
                    class="text-end">
                    Unique Count
                </th>
                <th scope="col"
                    class="text-end">
                    Attendance
                </th>
                <th scope="col"
                    class="text-end">
                    Direct
                </th>
                <th scope="col"
                    class="text-end">
                    Indirect
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>
                        <x-logo :storage_path="$event->organization->image_path_sm"
                                :default_path="'img/icons/organization-32x32.png'"
                                :width="32"
                                :height="32" />
                    </td>
                    <td>
                        <a href="{{ route('admin.events.update', compact('event')) }}">
                            {{ $event->name }}
                        </a>
                    </td>
                    <td>
                        {{ $event->event_shifts_count }}
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->unique_trooper_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->total_trooper_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->charity_direct_funds" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->charity_indirect_funds" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="7">
                    There are not any events that have been closed in the last {{ $lookback }} days.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('event_shifts_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('unique_trooper_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('total_trooper_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('charity_direct_funds')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('charity_indirect_funds')" />
                </th>
            </tr>
        </tfoot>

    </x-table>

@endsection