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
            <ul class="navbar-nav flex-row">
                <li class="nav-item"> <a href="{{ route('events.list') }}"
                       class="nav-link px-2 d-flex align-items-center">
                        <i class="fa fa-fw fa-list fa-lg me-2"></i>
                        <span class="d-none d-sm-inline me-1">Events </span>
                        List
                    </a>
                </li>
                <li class="nav-item"> <a href="{{ route('events.calendar') }}"
                       class="nav-link px-2 d-flex align-items-center">
                        <i class="fa fa-fw fa-calendar-days fa-lg me-2"></i>
                        <span class="d-none d-sm-inline me-1">Events </span>
                        Calendar
                    </a>
                </li>
            </ul>
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