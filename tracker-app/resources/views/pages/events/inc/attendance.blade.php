<x-section-title>Attendance</x-section-title>
<ul class="list-group list-group-flush mb-3">
    <li class="list-group-item d-flex justify-content-between">
        <span>Expected Attendees</span>
        <span class="fw-bold">
            <x-number-format :value="$event->expected_attendees"
                             :nulldisplay="'∞'" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Maximum Shifts Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->shifts_allowed"
                             :nulldisplay="'∞'" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Troopers Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->troopers_allowed"
                             :nulldisplay="'∞'" />
        </span>
    </li>
    <li class="list-group-item d-flex justify-content-between">
        <span>Handlers Allowed</span>
        <span class="fw-bold">
            <x-number-format :value="$event->handlers_allowed"
                             :nulldisplay="'∞'" />
        </span>
    </li>
</ul>