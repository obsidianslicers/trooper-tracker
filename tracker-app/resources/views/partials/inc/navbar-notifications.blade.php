@auth
    <li class="nav-item">
        <a class="nav-link position-relative {{ request()->routeIs('account.push-notifications') ? 'active' : '' }}"
           href="{{ route('account.push-notifications') }}">
            <i class="fa fa-fw fa-bell"></i>
            @if($pushNotificationUnreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size: 0.6rem;">
                    {{ $pushNotificationUnreadCount > 99 ? '99+' : $pushNotificationUnreadCount }}
                </span>
            @endif
        </a>
    </li>
@endauth
