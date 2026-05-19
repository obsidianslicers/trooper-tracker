@auth
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            Account
        </a>

        <ul class="dropdown-menu">
            <x-nav-link :url="route('account.profile')"
                        :active="request()->routeIs('account.*') && !request()->routeIs('account.push-notifications')">
                Profile
            </x-nav-link>
            <x-nav-link :url="route('account.costumes')">
                Costumes
            </x-nav-link>
            <x-nav-link :url="route('account.club-memberships')">
                Organizations
            </x-nav-link>
            <x-nav-link :url="route('auth.logout')">
                Logout
            </x-nav-link>
        </ul>
    </li>
@endauth