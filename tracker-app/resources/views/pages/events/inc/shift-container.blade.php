{{-- data-flash-target: messages from anything in the shift ("Saved.", "You're signed up.") show
     as toasts (resources/js/custom/toasts.js), so they don't push the page down --}}
@php($is_my_shift = $event_shift->isSignedUp(Auth::user()))
<div id="shift-container-{{ $event_shift->id }}"
     data-flash-target="toast">
    <span id="shift-{{ $event_shift->id }}"></span>
    <x-accordion-card :id="'shift-card-' . $event_shift->id"
                      :label="$event_shift->short_time_display"
                      :open="$count_of_shifts == 1 || $is_my_shift || (isset($open) && $open === true)">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift-header', compact('event_shift'))
        <div class="row mb-3">
            <div class="col-12 shift-signup-actions">
                @include('pages.events.inc.shift-add-trooper', compact('event_shift', 'event'))
            </div>
        </div>
        @include('pages.events.inc.same-day-shift-note', compact('event_shift', 'event'))
        @if($event_shift->event_troopers->isNotEmpty())
            <hr />
        @endif
        @foreach($event_shift->event_troopers as $event_trooper)
            @include('pages.events.inc.trooper', compact('event_trooper'))
        @endforeach
        @if($event->guests_allowed === null || $event->guests_allowed > 0)
            @if($event_shift->event_guests->count() > 0)
                <hr />
                <h5 class="mb-3">{{ __('events.Guests') }}</h5>
                @foreach($event_shift->event_guests as $event_guest)
                    @include('pages.events.inc.guest', compact('event_guest'))
                @endforeach
            @endif
        @endif
    </x-accordion-card>
</div>
@if(request()->hasHeader('HX-Request'))
    {{-- swapped in out of band, so the summary at the top of the page follows this shift's change --}}
    @include('pages.events.inc.my-signup-summary', ['event' => $event, 'oob' => true])
@endif
