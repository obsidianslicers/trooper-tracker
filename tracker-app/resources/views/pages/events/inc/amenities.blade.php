<x-section-title>Logistics &amp; Support</x-section-title>
<x-table>
    <tr>
        <td>
            Secure Staging
        </td>
        <td class="text-end">
            <x-yes-no class="me-2"
                      :value="$event->secure_staging_area" />
        </td>
    </tr>
    <tr>
        <td>
            Blasters Allowed
        </td>
        <td class="text-end">
            <x-yes-no class="me-2"
                      :value="$event->allow_blasters" />
        </td>
    </tr>
    <tr>
        <td>
            Props Allowed
        </td>
        <td class="text-end">
            <x-yes-no class="me-2"
                      :value="$event->allow_props" />
        </td>
    </tr>
    <tr>
        <td>
            Parking
        </td>
        <td class="text-end">
            <x-yes-no class="me-2"
                      :value="$event->parking_available" />
        </td>
    </tr>
    <tr>
        <td>
            Accessible
        </td>
        <td class="text-end">
            <x-yes-no class="me-2"
                      :value="$event->accessible" />
        </td>
    </tr>
</x-table>
@if($event->amenities)
    <p class="small text-muted mt-2 p-1">
        <b>Logistical Notes:</b> <i>{!! Str::markdown($event->amenities) !!}</i>
    </p>
@endif