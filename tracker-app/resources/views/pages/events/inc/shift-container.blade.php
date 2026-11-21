{{-- shifts the viewer is on start open, so they see their own sign-up without hunting for it --}}
@php($is_my_shift = $event_shift->isSignedUp(Auth::user()))
<div id="shift-container-{{ $event_shift->id }}">
    <span id="shift-{{ $event_shift->id }}"></span>
    <x-accordion-card :label="$event_shift->short_time_display"
                      :open="$count_of_shifts == 1 || $is_my_shift || (isset($open) && $open === true)">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift-header', compact('event_shift'))
        {{-- Sign Up, or "Your sign-up" once they're on the shift, above the roster --}}
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
