{{-- The same view switcher the Svelte events list shows, so every events page links to the others. --}}

@php
    $views = [
        'list' => ['route' => 'events.list', 'icon' => 'fa-list', 'label' => __('events.List')],
        'calendar' => ['route' => 'events.calendar', 'icon' => 'fa-calendar-days', 'label' => __('events.Calendar')],
        'map' => ['route' => 'events.map', 'icon' => 'fa-map', 'label' => __('events.Map')],
        'closed' => ['route' => 'events.closed', 'icon' => 'fa-circle-check', 'label' => __('events.Completed')],
        'cancelled' => ['route' => 'events.cancelled', 'icon' => 'fa-ban', 'label' => __('events.Cancelled')],
    ];

    if (!config('services.google.maps_api_key') && $active !== 'map')
    {
        unset($views['map']);
    }
@endphp

<div class="dropdown event-views-menu">
    <button type="button"
            class="btn btn-outline-light dropdown-toggle text-nowrap"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="{{ __('events.Event views') }}">
        <i class="fa fa-fw {{ $views[$active]['icon'] }} me-1"></i>{{ $views[$active]['label'] }}
    </button>
    <ul class="dropdown-menu">
        @foreach($views as $key => $view)
            <li>
                <a @class(['dropdown-item', 'active' => $key === $active])
                   href="{{ route($view['route']) }}"
                   @if($key === $active) aria-current="page" @endif>
                    <i class="fa fa-fw {{ $view['icon'] }} me-2"></i>{{ $view['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
