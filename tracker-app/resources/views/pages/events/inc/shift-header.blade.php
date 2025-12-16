<div class="row mb-3">
    <div class="col-6">
        <span class="text-muted">
            Shift Status:
        </span>
        {{ to_title($event_shift->status->name) }}
    </div>
    <div class="col-6 text-end">
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
            {{ $event_shift->event_troopers->count() }} signed up
        @endif
    </div>
</div>
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
            <a target="_blank"
               class="btn btn-sm btn-outline-primary"
               title="Download ICS File"
               href="{{ $link->ics() }}">
                <i class="fa fa-fw fa-calendar"></i>
            </a>
        </x-button-group>
    </div>
</div>
@endif