<div class="d-flex justify-content-between align-items-center mb-2">
    <x-section-title class="mb-0">Mission Roster</x-section-title>
    @if($can_moderate)
        <a class="btn btn-sm btn-outline-secondary"
           href="{{ route('events.download-roster-csv', compact('event')) }}">
            <i class="fa fa-fw fa-download me-1"></i>
            Download CSV (All Shifts)
        </a>
    @endif
</div>

@php($count_of_shifts = $event->event_shifts->count())
@if($count_of_shifts > 1)
    <div class="row mb-3">
        <div class="col-12">
            {{ $count_of_shifts }} Shifts Available
        </div>
    </div>
@endif
@foreach($event->event_shifts as $event_shift)
    @include('pages.events.inc.shift-container', compact('event_shift', 'event', 'count_of_shifts'))
@endforeach

@include('pages.events.inc.share-roster', compact('event', 'can_moderate'))

<x-modal-picker :id="'modal-trooper'"
                :label="'Find a Trooper'" />