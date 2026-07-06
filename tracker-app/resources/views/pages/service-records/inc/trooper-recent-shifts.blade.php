@php
    $pending_shifts = $pending_confirmation_shifts ?? collect();
@endphp

@if($pending_shifts->isNotEmpty() && auth()->id() === $trooper->id)
    <div class="service-record-confirmation mb-3 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa fa-fw fa-clipboard-check service-record-confirmation-icon"></i>
            <strong class="service-record-confirmation-title">
                {{ $pending_shifts->count() }} shift{{ $pending_shifts->count() === 1 ? '' : 's' }} need{{ $pending_shifts->count() === 1 ? 's' : '' }}
                confirmation
            </strong>
        </div>
        <div class="service-record-confirmation-list">
            @foreach($pending_shifts as $shift)
                <div class="service-record-confirmation-item">
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                        <div class="flex-grow-1">
                            <a href="{{ route('events.display', ['event' => $shift->event]) }}"
                               class="service-record-confirmation-link">
                                {{ $shift->event->name }}
                            </a>
                            <div class="service-record-confirmation-time">
                                {{ $shift->full_date_display }} &middot; {{ $shift->compact_time_display }}
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ $shift->event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::ATTENDED) }}"
                               class="btn btn-success btn-sm">Made It</a>
                            <a href="{{ $shift->event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND) }}"
                               class="btn btn-outline-danger btn-sm">Missed It</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div x-data="Events.Search.shiftsView()"
     x-init="init()">
    <!-- Toggle Button (Desktop Only) -->
    <div class="mb-3 d-none d-lg-block">
        <button x-on:click="toggleView()"
                class="btn btn-outline-secondary btn-sm">
            <span x-show="view === 'cards'">Switch to Table View</span>
            <span x-show="view === 'table'">Switch to Card View</span>
        </button>
    </div>

    <!-- Cards View -->
    <div x-show="view === 'cards'">
        <div class="row g-3">
            @forelse($recent_shifts as $shift)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header card-shift-header">
                            <div class="d-flex gap-2 align-items-start">
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
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Date & Time</small>
                                <p class="mb-0">{{ $shift->full_date_display }}</p>
                                <p class="text-muted small mb-0">{{ $shift->compact_time_display }}</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div>
                                <small class="text-muted d-block">Attended Costume</small>
                                @if($shift->event_trooper->is_handler)
                                    <p class="mb-0">Handler</p>
                                @elseif($shift->event_trooper->costume)
                                    <p class="mb-0"><strong>{{ $shift->event_trooper->costume->name }}</strong></p>
                                @else
                                    <p class="mb-0 text-muted">N/A</p>
                                @endif
                                @if($shift->event_trooper->attended && !empty($shift->event_trooper->credited_org_names))
                                    <div class="mt-1">
                                        <small class="text-muted d-block">Credited To</small>
                                        @foreach($shift->event_trooper->credited_org_names as $org_name)
                                            <span class="badge bg-secondary me-1">{{ $org_name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        No Historical Shifts ... Yet!
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
                    <th>Recent Shifts</th>
                    <th class="text-center">Date</th>
                    <th>Attended Costume</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent_shifts as $shift)
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
                            @elseif($shift->event_trooper->costume != null)
                                <b>{{ $shift->event_trooper->costume->name }}</b>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                            @if($shift->event_trooper->attended && !empty($shift->event_trooper->credited_org_names))
                                <div style="white-space: normal;"
                                     class="mt-1">
                                    <small class="text-muted d-block">Credited To</small>
                                    @foreach($shift->event_trooper->credited_org_names as $org_name)
                                        <span class="badge bg-secondary me-1">{{ $org_name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4">
                        No Recent Shifts ... Yet!
                    </x-table-empty>
                @endforelse
            </tbody>
        </x-table>
    </div>
</div>
