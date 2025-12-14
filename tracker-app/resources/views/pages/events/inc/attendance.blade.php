<x-section-title>Attendance</x-section-title>
<ul class="list-group list-group-flush mb-3">
    <li class="list-group-item d-flex justify-content-between">
        <span>Expected Attendees</span>
        <span class="fw-bold">{{ $event->expected_attendees ?? '-' }}</span>
    </li>
    @if($event->troopers_allowed != null)
        <li class="list-group-item d-flex justify-content-between">
            <span>Troopers Allowed</span>
            <span class="fw-bold">{{ $event->troopers_allowed ?? '-' }}</span>
        </li>
    @endif
    @if($event->handlers_allowed != null)
        <li class="list-group-item d-flex justify-content-between">
            <span>Handlers Allowed</span>
            <span class="fw-bold">{{ $event->handlers_allowed ?? '-' }}</span>
        </li>
    @endif
</ul>