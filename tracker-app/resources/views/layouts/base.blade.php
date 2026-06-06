<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('layouts.inc.head')
    @yield('page-meta')
    <title>{{ config('app.name') }} - Troop Tracker</title>
    @include('layouts.inc.scripts')
    @isset($page)
        @routes
        @inertiaHead
    @endisset
</head>

<body class="bg-black d-flex flex-column min-vh-100 theme-{{ Auth::user()->theme ?? 'stormtrooper' }}">
    @include('partials.navbar')
    @isset($page)
        @inertia
    @else
        @include('partials.bread-crumbs')

        <div>
            <h1 class="text-center py-3 site-title">
                @hasSection('page-title')
                    @yield('page-title')
                @else
                    @if(config('app.name') !== 'Troop Tracker')
                        {{ config('app.name') }}
                        <br />
                    @endif
                    Troop Tracker
                @endif
            </h1>
        </div>

        <div class="container rounded-3 shadow-sm p-4 mb-5 main-content">
            @include('partials.messages')
            <div class="row dashboard-row"></div>

            @yield('content')

        </div>

    @endisset

    @include('partials.footer')

</body>

</html>