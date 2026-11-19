@auth
    @unless(Auth::user()->is_denied || Auth::user()->is_pending)
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            {{ __('layout.Events') }}
        </a>

        <ul class="dropdown-menu">
            <x-nav-link :url="route('events.list')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-list fa-lg me-2"></i>
                {{ __('layout.List') }}
            </x-nav-link>
            <x-nav-link :url="route('events.calendar')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-calendar-days fa-lg me-2"></i>
                {{ __('layout.Calendar') }}
            </x-nav-link>
            @if(config('services.google.maps_api_key'))
                <x-nav-link :url="route('events.map')"
                            :active="request()->routeIs('events.*')">
                    <i class="fa fa-fw fa-map fa-lg me-2"></i>
                    {{ __('layout.Map') }}
                </x-nav-link>
            @endif
            <li>
                <hr class="dropdown-divider">
            </li>
            <x-nav-link :url="route('events.closed')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-circle-check fa-lg me-2"></i>
                {{ __('layout.Completed') }}
            </x-nav-link>
            <x-nav-link :url="route('events.cancelled')"
                        :active="request()->routeIs('events.*')">
                <i class="fa fa-fw fa-ban fa-lg me-2"></i>
                {{ __('layout.Cancelled') }}
            </x-nav-link>
            @can('create', \App\Models\Event::class)
                <li>
                    <hr class="dropdown-divider">
                </li>
                @role(['event_coordinator'])
                    <x-nav-link :url="route('admin.events.list')"
                                :active="request()->routeIs('admin.events.list')">
                        <i class="fa fa-fw fa-clipboard-list fa-lg me-2"></i>
                        {{ __('layout.My Events') }}
                    </x-nav-link>
                @endrole
                <x-nav-link :url="route('admin.events.create')"
                            :active="request()->routeIs('admin.events.create')">
                    <i class="fa fa-fw fa-plus fa-lg me-2"></i>
                    {{ __('layout.Create Event') }}
                </x-nav-link>
            @endcan
        </ul>
    </li>
    @endunless
@endauth