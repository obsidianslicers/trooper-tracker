@extends('layouts.base')

@section('page-title', __('admin.Costume Event Summary'))

@section('content')

    <x-card :label="__('admin.Filters')">
        <form method="GET"
              action="{{ route('admin.reports.costume-event-summary') }}"
              novalidate="novalidate">

            <div class="row g-3 align-items-end">
                <div class="col-sm-4">
                    <x-label value="Club" />
                    <x-input-select :property="'organization_id'"
                                    :placeholder="__('admin.All Clubs')"
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
                <x-submit-button>{{ __('admin.Run Report') }}</x-submit-button>
                <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}"
                   class="btn btn-outline-secondary">
                    {{ __('admin.Download CSV') }}
                </a>
            </div>

        </form>
    </x-card>

    <x-table class="caption-top">
        <caption>
            @if($organization_id)
                {{ $organizations->firstWhere('id', $organization_id)?->name }}
            @else
                {{ __('admin.All Clubs') }}
            @endif
            &mdash;
            @if($date_start || $date_end)
                @if($date_start) {{ __('admin.from :date', ['date' => $date_start->localFormat('M d, Y')]) }} @endif
                @if($date_end) {{ __('admin.to :date', ['date' => $date_end->localFormat('M d, Y')]) }} @endif
            @else
                {{ __('admin.all time') }}
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
                        {{ __('admin.Costume') }}{!! $sortIcon('name') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('events_count') }}" class="text-reset text-decoration-none">
                        {{ __('admin.Unique Events') }}{!! $sortIcon('events_count') !!}
                    </a>
                </th>
                <th scope="col" class="text-end">
                    <a href="{{ $sortLink('uses_count') }}" class="text-reset text-decoration-none">
                        {{ __('admin.Total Uses') }}{!! $sortIcon('uses_count') !!}
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
                    {{ __('admin.No costumes found with attended events in the selected date range.') }}
                </x-table-empty>
            @endforelse
        </tbody>
        <tfoot>
            @if($costume_events->count())
                <tr>
                    <th>{{ __('admin.Total') }}</th>
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
