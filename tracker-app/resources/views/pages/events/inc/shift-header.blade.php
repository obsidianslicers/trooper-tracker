<div class="row mb-3">
    <div class="col-6">
        <span class="text-muted">
            Shift Status:
        </span>
        {{ to_title($event_shift->status->name) }}
    </div>
    <div class="col-6 text-end">
        @php($count_of_troopers = $event_shift->event_troopers->filter(fn($et) => $et->intendsToGo())->count())
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
@if($can_moderate)
    <div class="row mb-3">
        <div class="col-12 text-end">
            <a class="btn btn-sm btn-outline-secondary d-none d-md-inline"
               href="{{ route('events.download-shift-roster-csv', ['event' => $event, 'event_shift' => $event_shift]) }}">
                <i class="fa fa-fw fa-download me-1"></i>
                Download CSV (This Shift)
            </a>
        </div>
    </div>
@endif
@if($event_shift->isSignedUp(Auth::user()))
<div class="row mb-3">
    <div class="col-12 text-end">
        @php($link = $event_shift->createCalendarLink())
        <x-button-group>
            <div class="btn btn-sm btn-primary">
                Add to Calendar
            </div>
            <a target="_blank"
               class="btn btn-sm btn-outline-primary"
               title="Create Google Calendar Event"
               href="{{ $link->google() }}">
                <i class="fa fa-brands fa-fw fa-google"></i>
            </a>
            <a target="_blank"
               class="btn btn-sm btn-outline-primary"
               title="Create Yahoo Calendar Event"
               href="{{ $link->yahoo() }}">
                <i class="fa fa-brands fa-fw fa-yahoo"></i>
            </a>
            <a target="_blank"
               class="btn btn-sm btn-outline-primary"
               title="Create Outlook Calendar Event"
               href="{{ $link->webOutlook() }}">
                <i class="fa fa-brands fa-fw fa-windows"></i>
            </a>
            <a class="btn btn-sm btn-outline-primary"
               title="Download ICS File"
               href="{{ route('events.download-shift-ics', ['event' => $event, 'event_shift' => $event_shift]) }}">
                <i class="fa fa-fw fa-calendar"></i>
            </a>
        </x-button-group>
    </div>
</div>
@endif