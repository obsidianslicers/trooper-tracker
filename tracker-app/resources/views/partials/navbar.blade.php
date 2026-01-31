<div class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        @auth
            <a class="navbar-brand"
               href="{{ route('events.list') }}">
                <img class="rounded p-0 m-0"
                     height="24px"
                     width="24px"
                     src="{{ url('img/icons/troop-tracker-32x32.png') }}" />
            </a>
        @else
            <a class="navbar-brand"
               href="{{ route('home') }}">
                <img class="rounded p-0 m-0"
                     height="24px"
                     width="24px"
                     src="{{ url('img/icons/troop-tracker-32x32.png') }}" />
                <span class="ms-1">Troop Tracker</span>
            </a>
        @endauth
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse"
             id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @if($forum_url = config('app.xenforo.base_url'))
                    <x-nav-link :url="$forum_url">
                        Forum
                    </x-nav-link>
                @endif
                @role(['administrator', 'moderator'])
                <x-nav-link :url="route('admin.display')"
                            :active="request()->routeIs('admin.*')">
                    Command Staff
                </x-nav-link>
                @endrole

                @auth
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
                                <i class="fa fa-fw fa-list fa-lg me-2"></i>
                                Completed
                            </x-nav-link>
                        </ul>
                    </li>

                    <x-nav-link :url="route('account.profile')"
                                :active="request()->routeIs('account.*')">
                        Account
                    </x-nav-link>
                    <x-nav-link :url="route('auth.logout')">
                        Logout
                    </x-nav-link>
                @else
                    <x-nav-link :url="route('auth.signup')"
                                :active="request()->routeIs('auth.signup')">
                        Sign Up
                    </x-nav-link>
                    <x-nav-link :url="route('auth.login')"
                                :active="request()->routeIs('auth.login')">
                        Login
                    </x-nav-link>
                @endauth

            </ul>
        </div>
    </div>
</div>