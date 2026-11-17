@extends('layouts.base')

@section('page-title', __('admin.Event Type Counts'))

@section('content')

    <x-table class="caption-top">
        <caption>
            {{ __('admin.Count of Event Types over the last :days days.', ['days' => $lookback]) }}
        </caption>
        <thead>
            <tr>
                <th scope="col">{{ __('admin.Event Type') }}</th>
                <th scope="col"
                    class="text-end">
                    {{ __('admin.Event Count') }}
                </th>
                <th scope="col"
                    class="text-end">
                    {{ __('admin.Unique Trooper Count') }}
                </th>
                <th scope="col"
                    class="text-end">
                    {{ __('admin.Total Trooper Attendance') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($event_types as $event_type)
                <tr>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.events.list', ['event_type_id' => $event_type->event_type->id]) }}">
                            {{ $event_type->event_type->name }}
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
                <x-table-empty :colspan="4">
                    {{ __('admin.There are not any events that have been closed in the last :days days.', ['days' => $lookback]) }}
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th scope="row">{{ __('admin.Total') }}</th>
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