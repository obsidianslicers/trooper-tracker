<div class="navbar navbar-expand-md navbar-dark bg-dark">
    <div class="container-fluid">
        @include('partials.inc.navbar-brand')
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse"
             id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @include('partials.inc.navbar-events')
                @auth
                    <x-nav-link :url="route('events.leaderboard')"
                                :active="request()->routeIs('events.leaderboard')">
                        Leaderboard
                    </x-nav-link>
                @endauth
                @if($forum_url = config('app.xenforo.base_url'))
                    <x-nav-link :url="$forum_url">
                        Forum
                    </x-nav-link>
                @endif
                @role(['administrator', 'moderator'])
                <x-nav-link :url="route('admin.display')"
                            :active="request()->routeIs('admin.*')">
                    Command Staff
                </x-nav-link>
                @endrole

                @include('partials.inc.navbar-auth')

            </ul>
        </div>
    </div>
</div>