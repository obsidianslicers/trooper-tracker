<div class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        @auth
            <a class="navbar-brand"
               href="{{ route('events.list') }}">
                <img class="rounded p-0 m-0"
                     height="24px"
                     width="24px"
                     src="{{ url('img/icons/troop-tracker-32x32.png') }}" />
                <span class="ms-1">Events / Troops</span>
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

{{--
<nav class="navbar navbar-dark navbar-expand-lg bg-black rounded-3 p-0">
    <div class="container-fluid justify-content-center">
        <!-- Hamburger toggle -->
        <button class="navbar-toggler ms-auto me-3 my-2"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#pillNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-center"
             id="pillNav">
            <ul class="navbar-nav flex-wrap">


            </ul>
        </div>
    </div>
</nav>
--}}