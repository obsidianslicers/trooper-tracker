@auth
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle"
           href="#"
           role="button"
           data-bs-toggle="dropdown">
            {{ __('layout.Account') }}
        </a>

        <ul class="dropdown-menu">
            @unless(Auth::user()->is_denied || Auth::user()->is_pending)
                <x-nav-link :url="route('account.index')"
                            :active="request()->routeIs('account.*') && !request()->routeIs('account.push-notifications')">
                    {{ __('layout.Profile') }}
                </x-nav-link>
            @endunless
            <x-nav-link :url="route('auth.logout')">
                {{ __('layout.Logout') }}
            </x-nav-link>
        </ul>
    </li>
@endauth