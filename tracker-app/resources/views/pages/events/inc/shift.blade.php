<div class="row mb-3">
    <div class="col-6 col-md-9">
        <span class="text-muted">
            Shift Status:
        </span>
        {{ to_title($event_shift->status->name) }}
    </div>
    <div class="col-6 col-md-3 text-end">
        @if($event_shift->troopers_allowed != null && $event_shift->event_troopers->count() >= $event_shift->troopers_allowed)
            <span class="text-success">
                FULL TROOP
                <i class="fa fa-fw fa-check-circle ms-2"></i>
            </span>
        @elseif($event_shift->event_troopers->count() == 0)
            <span class="text-danger">
                NOT ENOUGH!
            </span>
        @else
            {{ $event_shift->event_troopers->count() }} attending
        @endif
    </div>
</div>