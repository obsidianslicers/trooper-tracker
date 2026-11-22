{{-- The shift's status only when it isn't simply open, and how full it is --}}
@php
    $spots_left = $event_shift->trooperSpotsLeft();
    $stand_by_count = $event_shift->standByCount();
    $count_going = $event_shift->event_troopers->filter(fn ($event_trooper) => $event_trooper->status === \App\Enums\EventTrooperStatus::GOING)->count();
    $count_tentative = $event_shift->event_troopers->filter(fn ($event_trooper) => $event_trooper->status === \App\Enums\EventTrooperStatus::TENTATIVE)->count();
@endphp
<div class="shift-header">
    <span>
        @if($event_shift->status !== \App\Enums\EventStatus::OPEN)
            {{ $event_shift->status->label() }}
        @endif
    </span>
    @if($event_shift->is_open)
        @if($spots_left === 0)
            <span class="text-warning">
                {{ __('events.Full') }}@if($stand_by_count > 0) · {{ trans_choice('events.:count on stand-by|:count on stand-by', $stand_by_count) }}@endif
            </span>
        @elseif($spots_left !== null)
            {{-- counted the way the limit counts: troopers going, not handlers or tentative --}}
            <span class="text-muted">{{ __('events.:count of :limit going', ['count' => $event->troopers_allowed - $spots_left, 'limit' => $event->troopers_allowed]) }}@if($count_tentative > 0) · {{ trans_choice('events.:count tentative|:count tentative', $count_tentative) }}@endif</span>
        @else
            <span class="text-muted">{{ trans_choice('events.:count going|:count going', $count_going) }}@if($count_tentative > 0) · {{ trans_choice('events.:count tentative|:count tentative', $count_tentative) }}@endif</span>
        @endif
    @endif
</div>
@if($event_shift->usesStations())
    <div class="row mb-3">
        <div class="col-12">
            <div class="station-summary">
                @foreach($event_shift->event_shift_stations as $station)
                    <?php
                        $station_count = $event_shift->event_troopers
                            ->filter(fn ($event_trooper) => $event_trooper->event_shift_station_id === $station->id)
                            ->filter(fn ($event_trooper) => $event_trooper->status === \App\Enums\EventTrooperStatus::GOING)
                            ->count();
                        $is_full = $station->troopers_allowed !== null && $station_count >= $station->troopers_allowed;
                        $is_empty = $station_count === 0;
                        $modifier = $is_full ? 'station-summary__item--full' : ($is_empty ? 'station-summary__item--empty' : 'station-summary__item--open');
                    ?>
                    <div class="station-summary__item {{ $modifier }}">
                        <span class="station-summary__name">{{ $station->name }}</span>
                        <span class="station-summary__count">
                            {{ $station_count }}/{{ $station->troopers_allowed }}
                            @if($is_full)
                                <i class="fa fa-fw fa-check-circle ms-1"></i>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
