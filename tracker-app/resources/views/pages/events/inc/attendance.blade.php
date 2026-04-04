<x-section-title>Deployment Limits</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item bg-transparent border-bottom">
        Expected Attendees
        <span class="float-end">
            <x-number-format :value="$event->expected_attendees"
                             :nulldisplay="'alot'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        Maximum Shifts Allowed
        <span class="float-end">
            <x-number-format :value="$event->shifts_allowed"
                             :nulldisplay="'all'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        Troopers Allowed
        <span class="float-end">
            <x-number-format :value="$event->troopers_allowed"
                             :nulldisplay="'alot'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        Handlers Allowed
        <span class="float-end">
            <x-number-format :value="$event->handlers_allowed"
                             :nulldisplay="'alot'" />
        </span>
    </li>
</ul>