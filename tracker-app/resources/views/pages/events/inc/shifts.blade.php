<div id="mission-roster"
     class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <x-section-title class="mb-0">{{ __('events.Mission Roster') }}</x-section-title>
    <div class="d-flex flex-wrap align-items-center gap-3">
        @if($can_moderate)
            <a class="btn btn-link btn-sm p-0 text-muted d-none d-md-inline"
               href="{{ route('events.download-roster-csv', compact('event')) }}">
                {{ __('events.Download CSV (All Shifts)') }}
            </a>
            @if($event->is_active)
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        data-bs-toggle="modal"
                        data-bs-target="#share-roster-modal">
                    <i class="fa fa-fw fa-share-nodes me-1"></i>
                    {{ __('events.Share roster') }}
                </button>
            @endif
        @endif
        @auth
            {{-- replays the sign-up tour (pages/events/inc/signup-tour.blade.php) --}}
            <button type="button"
                    class="btn btn-sm btn-outline-secondary btn-secondary-action"
                    data-tour-replay
                    x-data
                    x-on:click="$dispatch('signup-tour:start')">
                {{ __('events.How sign-up works') }}
            </button>
        @endauth
    </div>
</div>

{{-- again where troopers sign up, so nobody signs up without seeing it. "One troop per day"
     isn't repeated here: the shift itself says so when it matters (same-day-shift-note) --}}
<x-not-counted :event="$event"
               class="mb-3" />

@auth
    @include('pages.events.inc.signup-tour', compact('event'))
@endauth
@php($count_of_shifts = $event->event_shifts->count())
@foreach($event->event_shifts as $event_shift)
    @include('pages.events.inc.shift-container', compact('event_shift', 'event', 'count_of_shifts'))
@endforeach

@if($can_moderate && $event->is_active)
    <div class="modal fade"
         id="share-roster-modal"
         tabindex="-1"
         aria-labelledby="share-roster-modal-title"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"
                        id="share-roster-modal-title">{{ __('events.Share roster') }}</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="{{ __('events.Close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('pages.events.inc.share-roster', compact('event', 'can_moderate'))
                </div>
            </div>
        </div>
    </div>
@endif
@include('pages.events.inc.broadcast-message', compact('event', 'can_moderate'))

<x-modal-picker :id="'modal-trooper'"
                :label="__('events.Find a Trooper')" />
