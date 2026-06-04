@vite(['resources/js/app.js', 'resources/svelte/app.ts'])
@yield('page-script')
@stack('scripts')

@auth
    @vite('resources/js/fcm-register.js')
@endauth