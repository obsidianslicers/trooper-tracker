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
            @auth
                @if(Auth::user()->membership_status?->value === 'active')
                    <form method="GET"
                          action="{{ route('search') }}"
                          class="d-flex ms-2 me-auto my-1 my-md-0"
                          role="search">
                        <div class="input-group input-group-sm flex-nowrap">
                            <input type="search"
                                   name="q"
                                   value="{{ request()->query('q') }}"
                                   placeholder="Search troopers, events, IDs..."
                                   class="form-control form-control-sm"
                                   style="min-width: 180px;"
                                   aria-label="Search" />
                            <button type="submit"
                                    class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-fw fa-search"></i>
                            </button>
                        </div>
                    </form>
                @endif
            @endauth
            <ul class="navbar-nav ms-auto">
                @include('partials.inc.navbar-events')
                @include('partials.inc.navbar-service-records')
                @auth
                @include('partials.inc.navbar-account')
                @unless(Auth::user()->is_denied)
                    @include('partials.inc.navbar-notifications')
                    @if(App\Facades\TroopTrackerFacade::isXenforoIntegrationConfigured())
                        <x-nav-link :url="config('services.xenforo.base_url')">
                            Forum
                        </x-nav-link>
                    @endif
                    @role(['administrator', 'moderator'])
                        <x-nav-link :url="route('admin.display')"
                                    :active="request()->routeIs('admin.*')">
                            <span class="text-warning">
                                Command Staff
                            </span>
                        </x-nav-link>
                    @endrole
                @endunless
                @else
                @include('partials.inc.navbar-auth')
                @endauth
                <x-nav-link :url="route('faq')" :active="request()->routeIs('faq')">
                    Help
                </x-nav-link>
            </ul>
        </div>
    </div>
</div>