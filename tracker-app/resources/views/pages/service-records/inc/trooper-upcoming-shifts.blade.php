<div x-data="Events.Search.shiftsView()"
     x-init="init()">
    <!-- Toggle Button (Desktop Only) -->
    <div class="mb-3 d-none d-lg-block">
        <button @click="toggleView()"
                class="btn btn-outline-secondary btn-sm">
            <span x-show="view === 'cards'">Switch to Table View</span>
            <span x-show="view === 'table'">Switch to Card View</span>
        </button>
    </div>

    <!-- Cards View -->
    <div x-show="view === 'cards'">
        <div class="row g-3">
            @forelse($upcoming_shifts as $shift)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-3 align-items-start">
                                <x-logo :storage_path="$shift->event->organization->image_path_sm ?? ''"
                                        :default_path="'img/icons/organization-32x32.png'"
                                        :width="32"
                                        :height="32" />
                                <h6 class="card-title mb-0 flex-grow-1">
                                    <a href="{{ route('events.display', ['event' => $shift->event]) }}">
                                        {{ $shift->event->name }}
                                    </a>
                                </h6>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Date & Time</small>
                                <p class="mb-0">{{ $shift->full_date_display }}</p>
                                <p class="text-muted small mb-0">{{ $shift->compact_time_display }}</p>
                            </div>

                            <div>
                                <small class="text-muted d-block">Planned Costume</small>
                                @if($shift->event_trooper->is_handler)
                                    <p class="mb-0">Handler</p>
                                @elseif($shift->event_trooper->costume)
                                    <p class="mb-0"><strong>{{ $shift->event_trooper->costume->name }}</strong></p>
                                    <p class="small text-muted mb-0">{{ $shift->event_trooper->costume_organizations }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        No Upcoming Shifts ... Yet!
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Table View -->
    <div x-show="view === 'table'">
        <x-table>
            <thead>
                <tr>
                    <th></th>
                    <th>Upcoming Shifts</th>
                    <th class="text-center">Date</th>
                    <th>Planned Costume</th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcoming_shifts as $shift)
                    <tr>
                        <td>
                            <x-logo :storage_path="$shift->event->organization->image_path_sm ?? ''"
                                    :default_path="'img/icons/organization-32x32.png'"
                                    :width="32"
                                    :height="32" />
                        </td>
                        <td>
                            <a href="{{ route('events.display', ['event' => $shift->event]) }}">
                                {{ $shift->event->name }}
                            </a>
                        </td>
                        <td class="text-start text-nowrap">
                            {{ $shift->full_date_display }}
                            <br />
                            <span class="text-muted">
                                {{ $shift->compact_time_display }}
                            </span>
                        </td>
                        <td class="text-start text-nowrap">
                            @if($shift->event_trooper->is_handler)
                                Handler
                            @else
                                @if($shift->event_trooper->costume != null)
                                    <b>
                                        {{ $shift->event_trooper->costume->name }}
                                    </b>
                                    <br />
                                    <i class="small text-muted">
                                        {{ $shift->event_trooper->costume_organizations }}
                                    </i>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4">
                        No Upcoming Shifts ... Yet!
                    </x-table-empty>
                @endforelse
            </tbody>
        </x-table>
    </div>
</div>

<script>
    window.Events = window.Events || {};
    window.Events.Search = window.Events.Search || {};

    window.Events.Search.shiftsView = function () {
        return {
            view: 'cards',
            init() {
                const saved = localStorage.getItem('upcomingShiftsView');
                if (saved) {
                    this.view = saved;
                } else {
                    // Default: cards on mobile, table on desktop
                    this.view = window.innerWidth < 992 ? 'cards' : 'table';
                }
            },
            toggleView() {
                this.view = this.view === 'cards' ? 'table' : 'cards';
                localStorage.setItem('upcomingShiftsView', this.view);
            }
        }
    }
</script>