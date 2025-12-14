<x-section-title>Amenities</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item">
        <x-yes-no class="me-2"
                  :value="$event->secure_staging_area" />
        Secure Staging
    </li>
    <li class="list-group-item">
        <x-yes-no class="me-2"
                  :value="$event->allow_blasters" />
        Blasters Allowed
    </li>
    <li class="list-group-item">
        <x-yes-no class="me-2"
                  :value="$event->allow_props" />
        Props Allowed
    </li>
    <li class="list-group-item">
        <x-yes-no class="me-2"
                  :value="$event->parking_available" />
        Parking
    </li>
    <li class="list-group-item">
        <x-yes-no class="me-2"
                  :value="$event->accessible" />
        Accessible
    </li>
</ul>
@if($event->amenities)
    <p class="small text-muted mt-2">
        {!! Str::markdown($event->amenities) !!}
    </p>
@endif