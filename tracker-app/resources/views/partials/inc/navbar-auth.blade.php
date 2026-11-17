<x-nav-link :url="route('auth.signup')"
            :active="request()->routeIs('auth.signup')">
    {{ __('layout.Sign Up') }}
</x-nav-link>
<x-nav-link :url="route('auth.login')"
            :active="request()->routeIs('auth.login')">
    {{ __('layout.Login') }}
</x-nav-link>