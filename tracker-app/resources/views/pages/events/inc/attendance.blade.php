<x-section-title>Deployment Limits</x-section-title>
<x-table>
    <tr>
        <td>Expected Attendees</td>
        <td class="text-end">
            <x-number-format :value="$event->expected_attendees" />
        </td>
    </tr>
    <tr>
        <td>Maximum Shifts Allowed</td>
        <td class="text-end">
            <x-number-format :value="$event->shifts_allowed" />
        </td>
    </tr>
    <tr>
        <td>Troopers Allowed</td>
        <td class="text-end">
            <x-number-format :value="$event->troopers_allowed" />
        </td>
    </tr>
    <tr>
        <td>Handlers Allowed</td>
        <td class="text-end">
            <x-number-format :value="$event->handlers_allowed" />
        </td>
    </tr>
</x-table>