@extends('layouts.base')

@section('page-title', 'Trooper Event Counts')

@section('content')

    <x-card label="Filters">
        <form method="GET"
              action="{{ route('admin.reports.trooper-event-summary') }}"
              novalidate="novalidate">

            <div class="row g-3 align-items-end">
                <div class="col-sm-4">
                    <x-label value="Date Start" />
                    <x-input-date :property="'date_start'"
                                  :value="$date_start?->format('Y-m-d') ?? ''" />
                </div>
                <div class="col-sm-4">
                    <x-label value="Date End" />
                    <x-input-date :property="'date_end'"
                                  :value="$date_end?->format('Y-m-d') ?? ''" />
                </div>
                <div class="col-sm-4 d-flex align-items-end pb-1">
                    <x-input-checkbox :property="'active_only'"
                                      :label="'Active Members Only'"
                                      :checked="$active_only" />
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <x-submit-button>Run Report</x-submit-button>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"
                   class="btn btn-outline-secondary">
                    Download CSV
                </a>
            </div>

        </form>
    </x-card>

    <x-table class="caption-top">
        <caption>
            @if($date_start || $date_end)
                Trooper Event Counts
                @if($date_start) from {{ $date_start->format('M d, Y') }} @endif
                @if($date_end) to {{ $date_end->format('M d, Y') }} @endif
            @else
                Trooper Event Counts (all time)
            @endif
            @if($active_only) &mdash; Active Members Only @endif
        </caption>
        <thead>
            <tr>
                <th>Trooper</th>
                <th scope="col"
                    class="text-end">
                    Unique Events
                </th>
                <th scope="col"
                    class="text-end">
                    Total Shifts
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trooper_events as $trooper_event)
                <tr>
                    <td>
                        <a href="{{ route('admin.troopers.profile', $trooper_event) }}">
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
                    No troopers found with attended events in the selected date range.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    {{ $trooper_events->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection
