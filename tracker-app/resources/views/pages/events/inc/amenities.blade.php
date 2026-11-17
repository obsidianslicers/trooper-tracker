<x-section-title>{{ __('events.Logistics & Support') }}</x-section-title>
<ul class="list-group list-group-flush">
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->secure_staging_area" />
        {{ __('events.Secure Staging') }}
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->allow_blasters" />
        {{ __('events.Blasters Allowed') }}
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->allow_props" />
        {{ __('events.Props Allowed') }}
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->parking_available" />
        {{ __('events.Parking Available') }}
    </li>
    <li class="list-group-item bg-transparent border-bottom">
        <x-yes-no class="me-2"
                  :value="$event->accessible" />
        {{ __('events.Accessible') }}
    </li>
</ul>
@if($event->amenities)
    <p class="small text-muted mt-2 p-1">
        <b>{{ __('events.Logistical Notes:') }}</b> <i>{!! Str::markdown($event->amenities) !!}</i>
    </p>
@endif