@extends('layouts.base')

@section('page-title', 'Donation Event Summary')

@section('content')

    <x-card label="Filters">
        <form method="GET"
              action="{{ route('admin.reports.donation-event-summary') }}"
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
                    <x-input-checkbox :property="'charity_only'"
                                      :label="'Events with charity data only'"
                                      :checked="$charity_only" />
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
                @if($date_start) from {{ $date_start->format('M d, Y') }} @endif
                @if($date_end) to {{ $date_end->format('M d, Y') }} @endif
            @else
                All time
            @endif
            @if($charity_only) &mdash; Charity data only @endif
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
                <th colspan="4"></th>
                <th scope="col" class="text-center" colspan="3">— Donations —</th>
                <th colspan="2"></th>
            </tr>
            <tr>
                <th>
                    <a href="{{ $sortLink('name') }}" class="text-reset text-decoration-none">
                        Event{!! $sortIcon('name') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ $sortLink('event_start') }}" class="text-reset text-decoration-none">
                        Date{!! $sortIcon('event_start') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ $sortLink('charity_name') }}" class="text-reset text-decoration-none">
                        Charity{!! $sortIcon('charity_name') !!}
                    </a>
                </th>
                <th>Notes</th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('charity_direct_funds') }}" class="text-reset text-decoration-none">
                        Direct{!! $sortIcon('charity_direct_funds') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('charity_indirect_funds') }}" class="text-reset text-decoration-none">
                        Indirect{!! $sortIcon('charity_indirect_funds') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">Total</th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('attendees_count') }}" class="text-reset text-decoration-none">
                        Attendees{!! $sortIcon('attendees_count') !!}
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                <tr>
                    <td>
                        <a href="{{ route('admin.events.update', compact('event')) }}">
                            {{ $event->name }}
                        </a>
                        @if($event->organization)
                            <br><small class="text-muted">{{ $event->organization->name }}</small>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        {{ $event->event_start?->format('M d, Y') }}
                    </td>
                    <td>{{ $event->charity_name }}</td>
                    <td><small>{{ $event->charity_notes }}</small></td>
                    <td class="text-end">
                        <x-number-format :value="$event->charity_direct_funds" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->charity_indirect_funds" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->charity_direct_funds + $event->charity_indirect_funds" />
                    </td>
                    <td class="text-end">
                        <x-number-format :value="$event->attendees_count" />
                    </td>
                </tr>
            @empty
                <x-table-empty :colspan="8">
                    No closed events found for the selected filters.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            @if($events->count())
            <tr>
                <th colspan="4">Total</th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('charity_direct_funds')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('charity_indirect_funds')" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum(fn($e) => $e->charity_direct_funds + $e->charity_indirect_funds)" />
                </th>
                <th class="text-end">
                    <x-number-format :value="$events->sum('attendees_count')" />
                </th>
            </tr>
            @endif
            <tr>
                <td colspan="8">
                    {{ $events->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection
