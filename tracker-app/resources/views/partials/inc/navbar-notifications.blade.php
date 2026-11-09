@auth
    <li class="nav-item dropdown">
        <a class="nav-link {{ request()->routeIs('account.push-notifications') ? 'active' : '' }}"
           href="#"
           role="button"
           data-bs-toggle="dropdown"
           aria-expanded="false"
           aria-label="Notifications">
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

        <div class="dropdown-menu dropdown-menu-end p-0 notifications-menu">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                <span class="fw-semibold">Notifications</span>
                @if($navbar_notifications->isNotEmpty())
                    <form method="POST"
                          action="{{ route('account.push-notifications.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-outline-secondary">
                            Clear all
                        </button>
                    </form>
                @endif
            </div>

            @forelse($navbar_notifications as $notification)
                <form method="POST"
                      action="{{ route('account.push-notifications.read', $notification) }}">
                    @csrf
                    <button type="submit"
                            class="dropdown-item d-flex justify-content-between align-items-start gap-3 py-2 text-wrap
                                   {{ $notification->isUnread() ? 'notification-unread fw-semibold' : '' }}">
                        <div>
                            <div>{{ $notification->title }}</div>
                            <div class="fw-normal small opacity-75 mt-1 text-break">{{ $notification->body }}</div>
                        </div>
                        <small class="fw-normal opacity-75 text-nowrap">
                            {{ $notification->created_at->diffForHumans(short: true) }}
                        </small>
                    </button>
                </form>
            @empty
                <p class="small opacity-75 px-3 py-3 mb-0">No notifications yet.</p>
            @endforelse

            <a class="dropdown-item text-center small border-top py-2"
               href="{{ route('account.push-notifications') }}">
                See all notifications
            </a>
        </div>
    </li>
@endauth
