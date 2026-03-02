<x-section-title>Mission Roster</x-section-title>

@include('pages.events.inc.share-roster', compact('event', 'can_moderate'))

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