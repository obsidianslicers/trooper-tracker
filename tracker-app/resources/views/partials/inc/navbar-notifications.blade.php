@auth
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('account.push-notifications') ? 'active' : '' }}"
           href="{{ route('account.push-notifications') }}">
            <span class="position-relative">
                <i class="fa fa-fw fa-bell"></i>
                @if($pushNotificationUnreadCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size: 0.6rem;">
                        {{ $pushNotificationUnreadCount > 99 ? '99+' : $pushNotificationUnreadCount }}
                    </span>
                @endif
            </span>
            <span class="d-md-none ms-1">Notifications</span>
        </a>
    </li>
@endauth
