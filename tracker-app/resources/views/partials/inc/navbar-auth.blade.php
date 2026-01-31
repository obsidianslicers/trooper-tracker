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