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
                <th scope="col" class="text-center" colspan="4">— Charity —</th>
                <th colspan="2"></th>
            </tr>
            <tr>
                <th>
                    <a href="{{ $sortLink('name') }}" class="text-reset text-decoration-none">
                        Event / Shift{!! $sortIcon('name') !!}
                    </a>
                </th>
                <th>
                    <a href="{{ $sortLink('event_start') }}" class="text-reset text-decoration-none">
                        Date{!! $sortIcon('event_start') !!}
                    </a>
                </th>
                <th>Charity</th>
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
                    <a href="{{ $sortLink('charity_hours') }}" class="text-reset text-decoration-none">
                        Hours{!! $sortIcon('charity_hours') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('attendees_count') }}" class="text-reset text-decoration-none">
                        Attendees{!! $sortIcon('attendees_count') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">Trooper Hrs</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php
                    $event_direct   = $event->event_shifts->sum('charity_direct_funds');
                    $event_indirect = $event->event_shifts->sum('charity_indirect_funds');
                    $event_hours    = $event->event_shifts->sum(fn($s) => $s->effective_charity_hours);
                    $event_attended = $event->event_shifts->sum('attendees_count');
                    $event_trooper_hours = $event->event_shifts->sum(fn($s) => $s->effective_charity_hours * $s->attendees_count);
                @endphp
                <tr class="table-secondary fw-semibold">
                    <td>
                        <a href="{{ route('admin.events.update', compact('event')) }}" class="text-reset">
                            {{ $event->name }}
                        </a>
                        @if($event->organization)
                            <br><small class="fw-normal text-muted">{{ $event->organization->name }}</small>
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $event->event_start?->format('M d, Y') }}</td>
                    <td colspan="2"></td>
                    <td class="text-end"><x-number-format :value="$event_direct" /></td>
                    <td class="text-end"><x-number-format :value="$event_indirect" /></td>
                    <td class="text-end"><x-number-format :value="$event_direct + $event_indirect" /></td>
                    <td class="text-end"><x-number-format :value="$event_hours" /></td>
                    <td class="text-end"><x-number-format :value="$event_attended" /></td>
                    <td class="text-end"><x-number-format :value="$event_trooper_hours" /></td>
                </tr>
                @foreach($event->event_shifts as $shift)
                    @php
                        $shift_trooper_hours = $shift->effective_charity_hours * $shift->attendees_count;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <small class="text-muted">{{ $shift->compact_time_display }}</small>
                        </td>
                        <td></td>
                        <td><small>{{ $shift->charity_name }}</small></td>
                        <td><small>{{ $shift->charity_notes }}</small></td>
                        <td class="text-end">
                            <x-number-format :value="$shift->charity_direct_funds" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$shift->charity_indirect_funds" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$shift->charity_direct_funds + $shift->charity_indirect_funds" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$shift->effective_charity_hours" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$shift->attendees_count" />
                        </td>
                        <td class="text-end">
                            <x-number-format :value="$shift_trooper_hours" />
                        </td>
                    </tr>
                @endforeach
            @empty
                <x-table-empty :colspan="10">
                    No closed events found for the selected filters.
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            @if($events->count())
            @php
                $total_direct        = $events->sum(fn($e) => $e->event_shifts->sum('charity_direct_funds'));
                $total_indirect      = $events->sum(fn($e) => $e->event_shifts->sum('charity_indirect_funds'));
                $total_hours         = $events->sum(fn($e) => $e->event_shifts->sum(fn($s) => $s->effective_charity_hours));
                $total_attended      = $events->sum(fn($e) => $e->event_shifts->sum('attendees_count'));
                $total_trooper_hours = $events->sum(fn($e) => $e->event_shifts->sum(fn($s) => $s->effective_charity_hours * $s->attendees_count));
            @endphp
            <tr>
                <th colspan="4">Total</th>
                <th class="text-end"><x-number-format :value="$total_direct" /></th>
                <th class="text-end"><x-number-format :value="$total_indirect" /></th>
                <th class="text-end"><x-number-format :value="$total_direct + $total_indirect" /></th>
                <th class="text-end"><x-number-format :value="$total_hours" /></th>
                <th class="text-end"><x-number-format :value="$total_attended" /></th>
                <th class="text-end"><x-number-format :value="$total_trooper_hours" /></th>
            </tr>
            @endif
            <tr>
                <td colspan="10">
                    {{ $events->links() }}
                </td>
            </tr>
        </tfoot>
    </x-table>

@endsection
