<x-section-title>Logistics &amp; Support</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->secure_staging_area" />
        Secure Staging
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->allow_blasters" />
        Blasters Allowed
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->allow_props" />
        Props Allowed
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->parking_available" />
        Parking Available
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->accessible" />
        Accessible
    </li>
</ul>
@if($event->amenities)
    <p class="small text-muted mt-2 p-1">
        <b>Logistical Notes:</b> <i>{!! Str::markdown($event->amenities) !!}</i>
    </p>
@endif