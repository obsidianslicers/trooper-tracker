<div id="shift-container-{{ $event_shift->id }}">
    <span id="shift-{{ $event_shift->id }}"></span>
    <x-accordion-card :label="$event_shift->short_time_display"
                      :open="$count_of_shifts == 1 || (isset($open) && $open === true)">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift-header', compact('event_shift'))
        @foreach($event_shift->event_troopers as $event_trooper)
            @include('pages.events.inc.trooper', compact('event_trooper'))
        @endforeach
        @if($event->guests_allowed === null || $event->guests_allowed > 0)
            @if($event_shift->event_guests->count() > 0)
                <hr />
                <h5 class="mb-3">Guests</h5>
                @foreach($event_shift->event_guests as $event_guest)
                    @include('pages.events.inc.guest', compact('event_guest'))
                @endforeach
            @endif
        @endif
        <div class="row my-3">
            <div class="col-12 shift-signup-actions">
                @include('pages.events.inc.shift-add-trooper', compact('event_shift', 'event'))
            </div>
        </div>
    </x-accordion-card>
</div>
