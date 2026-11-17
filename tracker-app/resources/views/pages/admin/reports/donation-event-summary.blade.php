@extends('layouts.base')

@section('page-title', __('admin.Donation Event Summary'))

@section('content')

    <x-card :label="__('admin.Filters')">
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
                                      :label="__('admin.Events with charity data only')"
                                      :checked="$charity_only" />
                </div>
            </div>

            @if($filter_orgs->count() > 1)
            <div class="row g-3 align-items-end mt-1">
                <div class="col-sm-4">
                    <x-label value="Clubs" />
                    <div class="border rounded p-2 overflow-auto" style="max-height: 200px;">
                        @foreach($filter_orgs as $org)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="organization_ids[]"
                                       value="{{ $org->id }}"
                                       id="org_{{ $org->id }}"
                                       @checked(empty($selected_ids) || in_array($org->id, $selected_ids))>
                                <label class="form-check-label" for="org_{{ $org->id }}">
                                    {{ $org->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <div class="row g-3 align-items-end mt-1">
                <div class="col-sm-4">
                    <x-label value="Sort By" />
                    <x-input-select :property="'sort'" :value="$sort" :options="[
                        'event_start'           => 'Date',
                        'name'                  => 'Event Name',
                        'charity_direct_funds'  => 'Direct Funds',
                        'charity_indirect_funds'=> 'Indirect Funds',
                        'charity_hours'         => 'Hours',
                        'attendees_count'       => 'Attendees',
                    ]" />
                </div>
                <div class="col-sm-4">
                    <x-label value="Direction" />
                    <x-input-select :property="'dir'" :value="$dir" :options="[
                        'desc' => 'Descending',
                        'asc'  => 'Ascending',
                    ]" />
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <x-submit-button>{{ __('admin.Run Report') }}</x-submit-button>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"
                   class="btn btn-outline-secondary">
                    {{ __('admin.Download CSV') }}
                </a>
            </div>

        </form>
    </x-card>

    @if($date_start || $date_end || $charity_only || !empty($selected_ids))
        <p class="text-muted small mb-2">
            @if(!empty($selected_ids))
                {{ $filter_orgs->whereIn('id', $selected_ids)->pluck('name')->join(', ') }}
            @endif
            @if($date_start) &mdash; {{ __('admin.From :date', ['date' => $date_start->localFormat('M d, Y')]) }} @endif
            @if($date_end) {{ __('admin.to :date', ['date' => $date_end->localFormat('M d, Y')]) }} @endif
            @if($charity_only) {{ __('admin.— Charity data only') }} @endif
        </p>
    @endif

    @forelse ($events as $event)
        @php
            $event_direct        = $event->event_shifts->sum('charity_direct_funds');
            $event_indirect      = $event->event_shifts->sum('charity_indirect_funds');
            $event_hours         = $event->event_shifts->sum(fn($s) => $s->effective_charity_hours);
            $event_attended      = $event->event_shifts->sum('attendees_count');
            $event_trooper_hours = $event->event_shifts->sum(fn($s) => $s->effective_charity_hours * $s->attendees_count);
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.events.update', compact('event')) }}"
                       class="fw-semibold text-decoration-none">
                        {{ $event->name }}
                    </a>
                    @if($event->organization)
                        <small class="text-muted ms-2">{{ $event->organization->name }}</small>
                    @endif
                </div>
                <div class="text-muted text-nowrap ms-3 small">
                    {{ $event->event_start?->localFormat('M d, Y') }}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin.Shift') }}</th>
                            <th>{{ __('admin.Charity') }}</th>
                            <th class="text-end">{{ __('admin.Direct') }}</th>
                            <th class="text-end">{{ __('admin.Indirect') }}</th>
                            <th class="text-end">{{ __('admin.Total') }}</th>
                            <th class="text-end">{{ __('admin.Hours') }}</th>
                            <th class="text-end">{{ __('admin.Attendees') }}</th>
                            <th class="text-end">{{ __('admin.Trooper Hrs') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event->event_shifts as $shift)
                            @php
                                $shift_total        = $shift->charity_direct_funds + $shift->charity_indirect_funds;
                                $shift_trooper_hrs  = $shift->effective_charity_hours * $shift->attendees_count;
                            @endphp
                            <tr>
                                <td class="text-nowrap">
                                    <small>{{ $shift->compact_time_display }}</small>
                                </td>
                                <td>
                                    {{ $shift->charity_name }}
                                    @if($shift->charity_notes)
                                        <small class="d-block text-muted">{{ $shift->charity_notes }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift->charity_direct_funds" decimals="cents" />
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift->charity_indirect_funds" decimals="cents" />
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift_total" decimals="cents" />
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift->effective_charity_hours" />
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift->attendees_count" />
                                </td>
                                <td class="text-end">
                                    <x-number-format :value="$shift_trooper_hrs" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted fst-italic">
                                    {{ __('admin.No shifts recorded.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($event->event_shifts->count() > 1)
                    <tfoot>
                        <tr class="fw-semibold table-light">
                            <td colspan="2">{{ __('admin.Event total') }}</td>
                            <td class="text-end"><x-number-format :value="$event_direct" /></td>
                            <td class="text-end"><x-number-format :value="$event_indirect" /></td>
                            <td class="text-end"><x-number-format :value="$event_direct + $event_indirect" /></td>
                            <td class="text-end"><x-number-format :value="$event_hours" /></td>
                            <td class="text-end"><x-number-format :value="$event_attended" /></td>
                            <td class="text-end"><x-number-format :value="$event_trooper_hours" /></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-info">{{ __('admin.No closed events found for the selected filters.') }}</div>
    @endforelse

    @if($events->hasPages())
        <div class="mb-3">{{ $events->links() }}</div>
    @endif

    @if($events->count())
        @php
            $total_direct        = $events->sum(fn($e) => $e->event_shifts->sum('charity_direct_funds'));
            $total_indirect      = $events->sum(fn($e) => $e->event_shifts->sum('charity_indirect_funds'));
            $total_hours         = $events->sum(fn($e) => $e->event_shifts->sum(fn($s) => $s->effective_charity_hours));
            $total_attended      = $events->sum(fn($e) => $e->event_shifts->sum('attendees_count'));
            $total_trooper_hours = $events->sum(fn($e) => $e->event_shifts->sum(fn($s) => $s->effective_charity_hours * $s->attendees_count));
        @endphp
        <div class="card">
            <div class="card-header fw-semibold">{{ __('admin.Grand Total') }}</div>
            <div class="card-body">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-3 text-center">
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Direct') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_direct" decimals="cents" /></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Indirect') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_indirect" decimals="cents" /></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Total Funds') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_direct + $total_indirect" decimals="cents" /></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Hours') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_hours" /></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Attendees') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_attended" /></div>
                    </div>
                    <div class="col">
                        <div class="text-muted small">{{ __('admin.Trooper Hrs') }}</div>
                        <div class="fw-semibold"><x-number-format :value="$total_trooper_hours" /></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
