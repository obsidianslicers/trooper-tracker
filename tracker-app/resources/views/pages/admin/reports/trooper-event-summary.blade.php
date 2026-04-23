@extends('layouts.base')

@section('page-title', 'Trooper Event Counts')

@section('content')

    <x-card label="Filters">
        <form method="GET"
              action="{{ route('admin.reports.trooper-event-summary') }}"
              novalidate="novalidate">

            <div class="row g-3 align-items-end">
                <div class="col-sm-4">
                    <x-label value="Club" />
                    <x-input-select :property="'organization_id'"
                                    :placeholder="'All Clubs'"
                                    :value="$organization_id"
                                    :options="$organizations->pluck('name', 'id')->toArray()" />
                </div>
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
            @if($organization_id)
                {{ $organizations->firstWhere('id', $organization_id)?->name }}
            @else
                All Clubs
            @endif
            &mdash;
            @if($date_start || $date_end)
                @if($date_start) from {{ $date_start->format('M d, Y') }} @endif
                @if($date_end) to {{ $date_end->format('M d, Y') }} @endif
            @else
                all time
            @endif
            @if($active_only) &mdash; Active Members Only @endif
        </caption>
        @php
            $sortLink = fn(string $col) => request()->fullUrlWithQuery([
                'sort' => $col,
                'dir'  => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                'page' => 1,
            ]);
            $sortIcon = fn(string $col) => $sort === $col
                ? ($dir === 'asc' ? ' ↑' : ' ↓')
                : '';
        @endphp
        <thead>
            <tr>
                <th>
                    <a href="{{ $sortLink('display_name') }}" class="text-reset text-decoration-none">
                        Trooper{!! $sortIcon('display_name') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('events_count') }}" class="text-reset text-decoration-none">
                        Unique Events{!! $sortIcon('events_count') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('event_shifts_count') }}" class="text-reset text-decoration-none">
                        Total Shifts{!! $sortIcon('event_shifts_count') !!}
                    </a>
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
