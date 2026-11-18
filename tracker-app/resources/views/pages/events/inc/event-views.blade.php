{{-- The same view switcher the Svelte events list shows, so every events page links to the others. --}}

@php
    $views = [
        'list' => ['route' => 'events.list', 'icon' => 'fa-list', 'label' => __('events.List')],
        'calendar' => ['route' => 'events.calendar', 'icon' => 'fa-calendar-days', 'label' => __('events.Calendar')],
        'map' => ['route' => 'events.map', 'icon' => 'fa-map', 'label' => __('events.Map')],
        'closed' => ['route' => 'events.closed', 'icon' => 'fa-circle-check', 'label' => __('events.Completed')],
        'cancelled' => ['route' => 'events.cancelled', 'icon' => 'fa-ban', 'label' => __('events.Cancelled')],
    ];

    if (!config('services.google.maps_api_key'))
    {
        unset($views['map']);
    }
@endphp

<nav class="d-flex flex-wrap justify-content-center gap-2 mb-3 event-views"
     aria-label="{{ __('events.Event views') }}">
    @foreach($views as $key => $view)
        @if($key === $active)
            <span class="btn btn-sm btn-primary active"
                  aria-current="page">
                <i class="fa fa-fw {{ $view['icon'] }} me-1"></i>{{ $view['label'] }}
            </span>
        @else
            <a class="btn btn-sm btn-outline-light"
               href="{{ route($view['route']) }}">
                <i class="fa fa-fw {{ $view['icon'] }} me-1"></i>{{ $view['label'] }}
            </a>
        @endif
    @endforeach
</nav>
