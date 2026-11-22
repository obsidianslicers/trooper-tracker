{{-- data-flash-target: messages from anything in the shift ("Saved.", "You're signed up.") show
     as toasts (resources/js/custom/toasts.js), so they don't push the page down.
     The roster keeps sign-up order: on limited shifts that order decides who goes and who moves up
     from stand-by, so rows are numbered, stand-by has its own numbered list, and Sign up sits at
     the end, where the new row will land. --}}
@php
    $is_my_shift = $event_shift->isSignedUp(Auth::user());
    $roster_holding = $event_shift->event_troopers->filter(fn ($event_trooper) => !in_array($event_trooper->status, [\App\Enums\EventTrooperStatus::STAND_BY, \App\Enums\EventTrooperStatus::CANCELLED], true))->values();
    $roster_stand_by = $event_shift->event_troopers->filter(fn ($event_trooper) => $event_trooper->status === \App\Enums\EventTrooperStatus::STAND_BY)->values();
    $roster_cancelled = $event_shift->event_troopers->filter(fn ($event_trooper) => $event_trooper->status === \App\Enums\EventTrooperStatus::CANCELLED)->values();
@endphp
<div id="shift-container-{{ $event_shift->id }}"
     data-flash-target="toast">
    <span id="shift-{{ $event_shift->id }}"></span>
    <x-accordion-card :id="'shift-card-' . $event_shift->id"
                      :label="$event_shift->short_time_display"
                      :open="$count_of_shifts == 1 || $is_my_shift || (isset($open) && $open === true)">
        <x-transmission-bar :id="'shift-' . $event_shift->id" />
        @include('pages.events.inc.shift-header', compact('event_shift'))
        @include('pages.events.inc.same-day-shift-note', compact('event_shift', 'event'))

        <div class="roster">
            @foreach($roster_holding as $event_trooper)
                @include('pages.events.inc.trooper', ['event_trooper' => $event_trooper, 'roster_number' => $loop->iteration])
            @endforeach

            @if($roster_stand_by->isNotEmpty())
                <div class="roster__heading">{{ __('events.Stand-by, in sign-up order') }}</div>
                @foreach($roster_stand_by as $event_trooper)
                    @include('pages.events.inc.trooper', ['event_trooper' => $event_trooper, 'roster_number' => $loop->iteration])
                @endforeach
            @endif

            <div class="shift-signup-actions">
                @include('pages.events.inc.shift-add-trooper', compact('event_shift', 'event'))
            </div>

            {{-- in the same grid, so the guests' columns line up with the troopers' --}}
            @if(($event->guests_allowed === null || $event->guests_allowed > 0) && $event_shift->event_guests->count() > 0)
                <div class="roster__heading">{{ __('events.Guests') }}</div>
                @foreach($event_shift->event_guests as $event_guest)
                    @include('pages.events.inc.guest', compact('event_guest'))
                @endforeach
            @endif

            @if($roster_cancelled->isNotEmpty())
                <div class="roster__heading">{{ __('events.Cancelled') }}</div>
                @foreach($roster_cancelled as $event_trooper)
                    @include('pages.events.inc.trooper', ['event_trooper' => $event_trooper, 'roster_number' => null])
                @endforeach
            @endif
        </div>

        @include('pages.events.inc.shift-links', compact('event_shift', 'event'))
    </x-accordion-card>
</div>
@if(request()->hasHeader('HX-Request'))
    {{-- swapped in out of band, so the summary at the top of the page follows this shift's change --}}
    @include('pages.events.inc.my-signup-summary', ['event' => $event, 'oob' => true])
@endif
