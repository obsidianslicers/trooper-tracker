<div class="navbar navbar-expand-md navbar-dark bg-dark">
  <div class="container-fluid">
    @auth
    <a class="navbar-brand"
       href="{{ route('home') }}">
      <img class="rounded p-0 m-0"
           height="24px"
           width="24px"
           src="{{ url('img/icons/troop-tracker-32x32.png') }}" />
      <span class="ms-1">Events / Troops</span>
    </a>
    @else
    <a class="navbar-brand"
       href="{{ route('home') }}">
      <img class="rounded p-0 m-0"
           height="24px"
           width="24px"
           src="{{ url('img/icons/troop-tracker-32x32.png') }}" />
      <span class="ms-1">Troop Tracker</span>
    </a>
    @endauth
    <button class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse"
         id="navbarNav">
      <ul class="navbar-nav ms-auto">
        @if(setting('forum_url') != '')
        <x-nav-link :url="setting('forum_url')">
          Forum
          <i class="fa fa-fw fa-external-link"></i>
        </x-nav-link>
        @endif
        @role(['administrator','moderator'])
        <x-nav-link :url="route('admin.display')"
                    :active="request()->routeIs('admin.*')">
          Command Staff
          <i class="fa fa-fw fa-toolbox"></i>
        </x-nav-link>
        @endrole

        @auth
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle"
             href="#"
             role="button"
             data-bs-toggle="dropdown"
             aria-expanded="false">
            Account
            <i class="fa fa-fw fa-id-card"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item"
                 href="{{ route('account.profile') }}">
                Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                 href="{{ route('account.notifications') }}">
                Notifications
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                 href="{{ route('account.costumes') }}">
                Costumes
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                 href="{{ route('auth.logout') }}">
                Logout
              </a>
            </li>
          </ul>
        </li>
        @else
        <x-nav-link :url="route('auth.register')"
                    :active="request()->routeIs('auth.register')">
          Register
        </x-nav-link>
        <x-nav-link :url="route('auth.login')"
                    :active="request()->routeIs('auth.login')">
          Login
        </x-nav-link>
        @endauth

      </ul>
    </div>
  </div>
</div>

{{--
<nav class="navbar navbar-dark navbar-expand-lg bg-black rounded-3 p-0">
  <div class="container-fluid justify-content-center">
    <!-- Hamburger toggle -->
    <button class="navbar-toggler ms-auto me-3 my-2"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#pillNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-center"
         id="pillNav">
      <ul class="navbar-nav flex-wrap">


      </ul>
    </div>
  </div>
</nav>
--}}