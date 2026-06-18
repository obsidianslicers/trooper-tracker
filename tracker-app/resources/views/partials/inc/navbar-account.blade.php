@auth
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            Account
        </a>

        <ul class="dropdown-menu">
            @unless(Auth::user()->is_denied || Auth::user()->is_pending)
                <x-nav-link :url="route('account.profile')"
                            :active="request()->routeIs('account.*') && !request()->routeIs('account.push-notifications')">
                    Profile
                </x-nav-link>
                <x-nav-link :url="route('account.notifications')">
                    Notifications
                </x-nav-link>
                <x-nav-link :url="route('account.costumes')">
                    Costumes
                </x-nav-link>
                <x-nav-link :url="route('account.club-memberships')">
                    Club Memberships
                </x-nav-link>
                @if(Auth::user()->minors()->exists())
                    <x-nav-link :url="route('account.minors')">
                        Cadets
                    </x-nav-link>
                @endif
            @endunless
            <x-nav-link :url="route('auth.logout')">
                Logout
            </x-nav-link>
        </ul>
    </li>
@endauth