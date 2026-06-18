@auth
    @unless(Auth::user()->is_denied)
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            Service Records
        </a>

        <ul class="dropdown-menu">
            <x-nav-link :url="route('service-records.trooper', ['trooper' => auth()->user()->id])"
                        :active="request()->routeIs('service-records.trooper')">
                My Service
            </x-nav-link>
            <x-nav-link :url="route('service-records.leaderboard')"
                        :active="request()->routeIs('service-records.leaderboard')">
                Leaderboard
            </x-nav-link>
            <x-nav-link :url="route('service-records.awards')"
                        :active="request()->routeIs('service-records.awards')">
                Awards
            </x-nav-link>
            <x-nav-link :url="route('service-records.achievements')"
                        :active="request()->routeIs('service-records.achievements')">
                Achievements
            </x-nav-link>
            <x-nav-link :url="route('service-records.photo-gallery')"
                        :active="request()->routeIs('service-records.photo-gallery')">
                Photo Gallery
            </x-nav-link>
            <x-nav-link :url="route('service-records.command-staff')"
                        :active="request()->routeIs('service-records.command-staff')">
                Command Staff
            </x-nav-link>
        </ul>
    </li>
    @endunless
@endauth