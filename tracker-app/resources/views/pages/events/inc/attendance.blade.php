<x-section-title>Deployment Limits</x-section-title>
<ul class="list-group list-group-flush mb-3">
    <li class="list-group-item d-flex justify-content-between">
        <span>Expected Attendees</span>
        <span class="fw-bold">
            <x-number-format :value="$event->expected_attendees" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Maximum Shifts Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->shifts_allowed" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Troopers Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->troopers_allowed" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Handlers Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->handlers_allowed" />
        </span>
    </li>
</ul>