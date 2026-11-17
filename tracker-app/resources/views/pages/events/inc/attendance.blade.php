<x-section-title>{{ __('events.Deployment Limits') }}</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item bg-transparent border-bottom">
        {{ __('events.Expected Attendees') }}
        <span class="float-end">
            <x-number-format :value="$event->expected_attendees"
                             :nulldisplay="'a lot'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        {{ __('events.Maximum Shifts Allowed') }}
        <span class="float-end">
            <x-number-format :value="$event->shifts_allowed"
                             :nulldisplay="'all'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        {{ __('events.Troopers Allowed') }}
        <span class="float-end">
            <x-number-format :value="$event->troopers_allowed"
                             :nulldisplay="'a lot'" />
        </span>
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        {{ __('events.Handlers Allowed') }}
        <span class="float-end">
            <x-number-format :value="$event->handlers_allowed"
                             :nulldisplay="'a lot'" />
        </span>
    </li>
</ul>