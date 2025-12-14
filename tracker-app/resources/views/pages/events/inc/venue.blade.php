<x-section-title>Venue</x-section-title>
<div class="row">
  <div class="col-12">
    {{ $event->venue }}
  </div>
  <div class="col-12">
    <a href="https://www.google.com/maps/search/?api=1&query={{ $event->venue_address }}"
       target="_blank"
       class="text-decoration-none d-flex align-items-center">
      <span>
        <i class="fa fa-fw fa-location-dot me-2"></i>
      </span>
      <span class="p-1 text-white">
        {{ $event->venue_address }}
      </span>
    </a>
  </div>
  @if($event->event_website && Str::isUrl($event->event_website))
  <div class="col-12">
    <a href="https://www.google.com/maps/search/?api=1&query={{ $event->venue_address }}"
       target="_blank"
       class="text-decoration-none d-flex align-items-center">
      <span>
        <i class="fa fa-fw fa-globe me-2"></i>
      </span>
      <span class="p-1 text-white">
        Event Website
      </span>
    </a>
  </div>
  @endif
</div>