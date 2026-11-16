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
        @pluginPageSlot('page.top')

        <div class="row dashboard-row"></div>

        @yield('content')
        @pluginPageSlot('page.bottom')

    </div>

    @include('partials.footer')
    <script type="application/json" id="tt-i18n">{!! json_encode([
        'locale' => app()->getLocale(),
        'messages' => app(\App\Services\TranslationService::class)->groups('common'),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite(['resources/js/app.js'])
    @yield('page-script')
    @stack('scripts')
    @pluginPageSlot('page.scripts')

    @auth
        @vite('resources/js/fcm-register.js')
    @endauth

</body>

</html>