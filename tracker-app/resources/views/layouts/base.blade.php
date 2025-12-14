<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible"
          content="ie=edge" />
    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>{{ setting('site_name') }} - Troop Tracker</title>

    <link rel="icon"
          href="{{ url('img/favicon.png') }}"
          type="image/x-icon">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.2.3/darkly/bootstrap.min.css" />
    @vite(['resources/css/app.scss'])

</head>

<body class="bg-black d-flex flex-column min-vh-100 theme-{{ Auth::user()->theme ?? 'stormtrooper' }}">
    @include('partials.navbar')
    @include('partials.bread-crumbs')

    <div>
        <h1 class="text-center py-3 site-title">
            @hasSection('page-title')
                @yield('page-title')
            @else
                {{ setting('site_name') }}
                <br />
                Troop Tracker
            @endif
        </h1>
    </div>

    <div class="container rounded-3 shadow-sm p-4 mb-5 main-content">
        @include('partials.messages')
        <div class="row dashboard-row"></div>

        @yield('content')

        @include('partials.footer')
    </div>

    @vite(['resources/js/app.js'])
    @yield('page-script')
    @stack('scripts')

</body>

</html>