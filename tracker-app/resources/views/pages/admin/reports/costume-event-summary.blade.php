@extends('layouts.base')

@section('page-title', 'Costume Event Summary')

@section('content')

    <x-card label="Filters">
        <form method="GET"
              action="{{ route('admin.reports.costume-event-summary') }}"
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
                    <a href="{{ $sortLink('name') }}" class="text-reset text-decoration-none">
                        Costume{!! $sortIcon('name') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('events_count') }}" class="text-reset text-decoration-none">
                        Unique Events{!! $sortIcon('events_count') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('uses_count') }}" class="text-reset text-decoration-none">
                        Total Uses{!! $sortIcon('uses_count') !!}
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($costume_events as $costume_event)
                <tr>
                    <td>{{ $costume_event->name }}</td>
                    <td class="text-end">
                        <x-number-format :value="$costume_event->events_count" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$costume_event->uses_count" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="3">
                    No costumes found with attended events in the selected date range.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            @if($costume_events->count())
                <tr>
                    <th>Total</th>
                    <th class="text-end">
                        <x-number-format :value="$costume_events->sum('events_count')" />
                    </th>
                    <th class="text-end">
                        <x-number-format :value="$costume_events->sum('uses_count')" />
                    </th>
                </tr>
            @endif
            <tr>
                <td colspan="3">
                    {{ $costume_events->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection
