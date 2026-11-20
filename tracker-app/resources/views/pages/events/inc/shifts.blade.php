<div class="d-flex justify-content-between align-items-center mb-2">
    <x-section-title class="mb-0">{{ __('events.Mission Roster') }}</x-section-title>
    <div class="d-flex gap-2">
        @auth
            {{-- replays the sign-up tour (pages/events/inc/signup-tour.blade.php) --}}
            <button type="button"
                    class="btn btn-sm btn-outline-info"
                    data-tour-replay
                    x-data
                    x-on:click="$dispatch('signup-tour:start')">
                <i class="fa fa-fw fa-circle-question me-1"></i>
                {{ __('events.How sign-up works') }}
            </button>
        @endauth
        @if($can_moderate)
            <a class="btn btn-sm btn-outline-secondary d-none d-md-inline"
               href="{{ route('events.download-roster-csv', compact('event')) }}">
                <i class="fa fa-fw fa-download me-1"></i>
                {{ __('events.Download CSV (All Shifts)') }}
            </a>
        @endif
    </div>
</div>

{{-- again where troopers sign up, so nobody signs up without seeing it. "One troop per day"
     isn't repeated here: the shift itself says so when it matters (same-day-shift-note) --}}
<x-not-counted :event="$event"
               class="mb-3" />

@php($count_of_shifts = $event->event_shifts->count())
@if($count_of_shifts > 1)
    <div class="row mb-3">
        <div class="col-12">
            {{ trans_choice('events.:count Shift Available|:count Shifts Available', $count_of_shifts) }}
        </div>
    </div>
@endif
@auth
    @include('pages.events.inc.signup-tour', compact('event'))
@endauth
@foreach($event->event_shifts as $event_shift)
    @include('pages.events.inc.shift-container', compact('event_shift', 'event', 'count_of_shifts'))
@endforeach

@include('pages.events.inc.share-roster', compact('event', 'can_moderate'))
@include('pages.events.inc.broadcast-message', compact('event', 'can_moderate'))

<x-modal-picker :id="'modal-trooper'"
                :label="__('events.Find a Trooper')" />