<x-section-title>Troopers</x-section-title>

@php($shifts_count = $event->event_shifts->count())
@if($shifts_count > 1)
    <div class="row mb-3">
        <div class="col-12">
            {{ $shifts_count }} Shifts Available
        </div>
    </div>
@endif
@foreach($event->event_shifts as $event_shift)
    @include('pages.events.inc.shift-container', compact('event_shift', 'event'))
@endforeach