@auth
    @unless(Auth::user()->is_denied || Auth::user()->is_pending)
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            Events
        </a>

        <ul class="dropdown-menu">
            <x-nav-link :url="route('events.list')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-list fa-lg me-2"></i>
                List
            </x-nav-link>
            <x-nav-link :url="route('events.calendar')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-calendar-days fa-lg me-2"></i>
                Calendar
            </x-nav-link>
            @if(config('services.google.maps_api_key'))
                <x-nav-link :url="route('events.map')"
                            :active="request()->routeIs('events.*')">
                    <i class="fa fa-fw fa-map fa-lg me-2"></i>
                    Map
                </x-nav-link>
            @endif
            <li>
                <hr class="dropdown-divider">
            </li>
            <x-nav-link :url="route('events.closed')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-circle-check fa-lg me-2"></i>
                Completed
            </x-nav-link>
            <x-nav-link :url="route('events.cancelled')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-ban fa-lg me-2"></i>
                Cancelled
            </x-nav-link>
            @role(['event_coordinator'])
                <li>
                    <hr class="dropdown-divider">
                </li>
                <x-nav-link :url="route('admin.events.list')"
                            :active="request()->routeIs('admin.events.list')">
                    <i class="fa fa-fw fa-clipboard-list fa-lg me-2"></i>
                    My Events
                </x-nav-link>
                <x-nav-link :url="route('admin.events.create')"
                            :active="request()->routeIs('admin.events.create')">
                    <i class="fa fa-fw fa-plus fa-lg me-2"></i>
                    Create Event
                </x-nav-link>
            @endrole
        </ul>
    </li>
    @endunless
@endauth