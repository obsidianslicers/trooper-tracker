<div class="row mb-3">
    <div class="col-6">
        <b class="py-3">{{ $event_shift->short_time_display }}</b>
        <br />
        <span class="text-muted">
            Shift Status:
        </span>
        {{ to_title($event_shift->status->name) }}
    </div>
    <div class="col-6 text-end">
        @php($count_of_troopers = $event_shift->event_troopers->filter(fn($et) => $et->is_going)->count())
        @if($event_shift->is_open)
            @if($event_shift->troopers_allowed != null && $count_of_troopers >= $event_shift->troopers_allowed)
                <span class="text-success">
                    FULL TROOP
                    <i class="fa fa-fw fa-check-circle ms-2"></i>
                </span>
            @elseif($count_of_troopers == 0)
                <span class="text-danger">
                    NOT ENOUGH!
                </span>
            @else
                {{ $count_of_troopers }}
                @if($event->troopers_allowed !== null && $event->troopers_allowed > 0)
                    / {{ $event->troopers_allowed }}
                @endif
                going
            @endif
        @endif
    </div>
</div>