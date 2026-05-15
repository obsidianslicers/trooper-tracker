<x-nav-link :url="route('auth.signup')"
            :active="request()->routeIs('auth.signup')">
    Sign Up
</x-nav-link>
<x-nav-link :url="route('auth.login')"
            :active="request()->routeIs('auth.login')">
    Login
</x-nav-link>