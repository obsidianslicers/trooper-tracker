@extends('layouts.base')

@section('page-title', 'Event Volunteer Counts')

@section('content')

    <x-table class="caption-top">
        <caption>
            Count of Event Volunteers over the last {{ $lookback }} days.
        </caption>
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>
                    Name
                </th>
                <th>
                    Shifts
                </th>
                <th>
                    Organization
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
            @forelse ($event_volunteers as $event_volunteer)
                <tr>
                    <td>
                        <x-logo :storage_path="$event_volunteer->organization->image_path_sm"
                                :default_path="'img/icons/organization-32x32.png'"
                                :width="32"
                                :height="32" />
                    </td>
                    <td>
                        <a href="{{ route('admin.events.update', compact('event_volunteer')) }}">
                            {{ $event_volunteer->name }}
                        </a>
                    </td>
                    <td>
                        {{ $event_volunteer->event_shifts_count }}
                    </td>
                    <td>
                        <a href="{{ route('admin.events.list', qs(['organization_id' => $event_volunteer->organization_id])) }}">
                            {{ $event_volunteer->organization->name }}
                        </a>
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event_volunteer->unique_trooper_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event_volunteer->total_trooper_count" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        There are not any events that have been closed in the last {{ $lookback }} days.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">Total</th>
                <th></th>
                <th></th>
                <th class="text-end">
                    <x-number-format :value="$event_volunteers->sum('count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$event_volunteers->sum('unique_trooper_count')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$event_volunteers->sum('total_trooper_count')" />
                </th>
            </tr>
        </tfoot>

    </x-table>

@endsection