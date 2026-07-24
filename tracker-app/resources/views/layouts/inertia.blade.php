<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Meta Data -->
    @include('partials.head')
    @yield('page-meta')

    @vite(['resources/svelte/app.ts'])
    @routes
    @inertiaHead
</head>

<body class="bg-black d-flex flex-column min-vh-100 theme-{{ Auth::user()->theme ?? 'stormtrooper' }}">
    @include('partials.navbar')

    @inertia

    @include('partials.footer')
    @yield('page-script')
    @stack('scripts')

    @auth
        @vite('resources/js/fcm-register.js')
    @endauth

</body>

</html>