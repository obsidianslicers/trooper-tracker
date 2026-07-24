<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Meta Data -->
    @yield('page-meta')
    @include('partials.head')
</head>

<body class="bg-black d-flex flex-column min-vh-100 theme-{{ Auth::user()->theme ?? 'stormtrooper' }}">
    @include('partials.navbar')
    @include('partials.bread-crumbs')

    <div>
        <h1 class="text-center py-3 site-title">
            @hasSection('page-title')
                @yield('page-title')
            @else
                {{ config('app.name') }}
                <br />
                Troop Tracker
            @endif
        </h1>
    </div>

    <div class="container rounded-3 shadow-sm p-4 mb-5 main-content">
        @include('partials.messages')
        @include('partials.account-delete-notice')

        <div class="row dashboard-row"></div>

        @yield('content')

    </div>

    @include('partials.footer')
    @vite(['resources/js/app.js'])
    @yield('page-script')
    @stack('scripts')

    @auth
        @vite('resources/js/fcm-register.js')
    @endauth

</body>

</html>