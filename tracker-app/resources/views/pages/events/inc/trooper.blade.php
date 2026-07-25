<div class="row mb-3 trooper-status-{{ $event_trooper->status->value }}">
    <div class="col-7 col-md-4 order-1 order-md-1">
        <i class="d-none fa fa-fw fa-times pe-2 text-danger cancelled-icon"></i>
        <a href="{{ route('service-records.trooper', ['trooper' => $event_trooper->trooper->id]) }}">
            {{ $event_trooper->trooper->display_name }}
        </a>
        <br/>
        <span class="text-muted small">{{ $event_trooper->trooper->legal_name }}</span>
        @if($event_trooper->added_by_trooper_id > 0)
            <br />
            <i class="small text-muted">
                <i class="fa fa-fw fa-user-plus"></i>
                {{ $event_trooper->added_by_trooper->display_name }}
            </i>
        @endif
    </div>
    <div class="col-12 col-md-5 order-3 order-md-2">
        @if($event_shift->usesStations())
            @if($event_trooper->canUpdateCostume(Auth::user()))
                <x-input-select :property="'event_shift_station_id'"
                                :options="$event_shift->event_shift_stations->pluck('name', 'id')->toArray()"
                                :value="$event_trooper->event_shift_station_id"
                                :placeholder="'-- Select Station --'"
                                hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                                hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                                hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-swap="outerHTML"
                                class="form-select-sm mt-2 mt-md-0 mb-2" />
            @elseif($event_trooper->event_shift_station)
                <div class="small text-muted mb-1">
                    <i class="fa fa-fw fa-location-dot me-1"></i>
                    {{ $event_trooper->event_shift_station->name }}
                </div>
            @endif
        @endif
        @if($event_trooper->canUpdateCostume(Auth::user()))
            @php
                $eligible_orgs_for_change = $event_trooper->trooper->eligibleOrgsForEvent($event);
                $limited_org_ids_for_change = $event->event_organizations
                    ->filter(fn ($o) => $o->troopers_allowed !== null || $o->handlers_allowed !== null)
                    ->pluck('organization_id')
                    ->toArray();
                $show_org_picker = $eligible_orgs_for_change->whereIn('id', $limited_org_ids_for_change)->isNotEmpty();
            @endphp
            @if($show_org_picker && $eligible_orgs_for_change->count() > 1)
                <x-input-select :property="'organization_id'"
                                :options="$eligible_orgs_for_change->pluck('name', 'id')->toArray()"
                                :value="$event_trooper->organization_id"
                                :placeholder="'-- Select Organization --'"
                                hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                                hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                                hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-swap="outerHTML"
                                class="form-select-sm mt-2 mt-md-0" />
            @elseif($show_org_picker && $eligible_orgs_for_change->count() === 1)
                <div class="small text-muted">
                    <i class="fa fa-fw fa-building me-1"></i>
                    Trooping as {{ $eligible_orgs_for_change->first()->name }}
                </div>
            @endif
            @php
                $costume_options = ['none' => 'Attending without a costume'] + $event_trooper->costumes;
            @endphp
            <x-input-select :property="'costume_id'"
                            :options="$costume_options"
                            :value="$event_trooper->is_attending_without_costume ? 'none' : $event_trooper->costume_id"
                            :placeholder="'-- Select Costume --'"
                            hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                            hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                            hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                            hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                            hx-swap="outerHTML"
                            class="form-select-sm mt-2 mt-md-0" />
            <x-input-select :property="'backup_costume_id'"
                            :options="$event_trooper->costumes"
                            :value="$event_trooper->backup_costume_id"
                            :placeholder="'-- Select Backup Costume (optional) --'"
                            hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                            hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                            hx-swap="none"
                            class="form-select-sm mt-2 mt-md-0" />
        @else
            @if($event_trooper->costume === null)
                @if($event_trooper->is_handler)
                    Handler
                @else
                    <i class="small text-muted">
                        No costume selected
                    </i>
                @endif
            @else
                <b>
                    {{ $event_trooper->costume->name }}
                </b>
                <br />
                <i class="small text-muted">
                    {{ $event_trooper->costume_organizations }}
                </i>
            @endif
            @if($event_trooper->backup_costume != null)
                <br />
                <i class="small text-muted">
                    <i class="fa fa-fw fa-box-archive"></i>
                    {{ $event_trooper->backup_costume->name }}
                    <br />
                    {{ $event_trooper->backup_costume_organizations }}
                </i>
            @endif
        @endif
    </div>
    <div class="col-12 col-md-3 order-2 order-md-3 text-md-end">
        <div class="ps-md-0">
            @if(
                    $can_moderate
                    && $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_shift->is_open
                    && in_array($event_trooper->status, [\App\Enums\EventTrooperStatus::STAND_BY, \App\Enums\EventTrooperStatus::GOING], true)
                )
                <div class="d-flex gap-1 justify-content-end">
                    @if($event_trooper->status === \App\Enums\EventTrooperStatus::STAND_BY)
                        <form hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                              hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                              hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                              hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                              hx-swap="outerHTML">
                            @csrf
                            <input type="hidden"
                                   name="status"
                                   value="{{ \App\Enums\EventTrooperStatus::GOING->value }}" />
                            <button type="submit"
                                    class="btn btn-sm btn-success">
                                <i class="fa fa-fw fa-check me-1"></i>
                                Approve
                            </button>
                        </form>
                    @endif
                    @if($event_trooper->status === \App\Enums\EventTrooperStatus::GOING)
                        <form hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                              hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                              hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                              hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                              hx-swap="outerHTML">
                            @csrf
                            <input type="hidden"
                                   name="status"
                                   value="{{ \App\Enums\EventTrooperStatus::STAND_BY->value }}" />
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="fa fa-fw fa-ban me-1"></i>
                                Reject
                            </button>
                        </form>
                    @endif
                </div>
            @elseif($event_shift->is_open && $event_trooper->status !== \App\Enums\EventTrooperStatus::PENDING && $event_trooper->canUpdateStatus(Auth::user()))
                <x-input-select :property="'status'"
                                :options="\App\Enums\EventTrooperStatus::toSignUpArray($event->tentative_signups_allowed, $event->hasLimits())"
                                :value="$event_trooper->status->value"
                                hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                                hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                                hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                                hx-swap="outerHTML"
                                class="form-select-sm" />
            @elseif($event_shift->is_closed && $event_shift->shift_ends_at->isPast() && $event_trooper->isEligibleForAttendance() && ($can_moderate || $event_trooper->canMarkAttendance(Auth::user())))
                @if($event_trooper->status === \App\Enums\EventTrooperStatus::ATTENDED || $event_trooper->status === \App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND)
                    <span class="{{ $event_trooper->status->color() }} d-block mb-1">
                        {{ to_title($event_trooper->status->name) }}
                        <span class="d-none d-md-inline">
                            {!! $event_trooper->status->iconTag() !!}
                        </span>
                    </span>
                @else
                    <div class="input-group input-group-sm">
                        <a class="btn {{ $event_trooper->status === \App\Enums\EventTrooperStatus::ATTENDED ? 'btn-success' : 'btn-outline-success' }}"
                           href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::ATTENDED) }}">
                            Made It
                        </a>
                        <a class="btn {{ $event_trooper->status === \App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND ? 'btn-danger' : 'btn-outline-danger' }}"
                           href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND) }}">
                            Missed It
                        </a>
                    </div>                
                @endif
            @elseif($event_trooper->status === \App\Enums\EventTrooperStatus::STAND_BY && $event_trooper->canCancel(Auth::user()))
                <span class="{{ $event_trooper->status->color() }} d-block mb-1">
                    {{ to_title($event_trooper->status->name) }}
                    <span class="d-none d-md-inline">
                        {!! $event_trooper->status->iconTag() !!}
                    </span>
                </span>
                <form hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                      hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                      hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                      hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                      hx-swap="outerHTML">
                    @csrf
                    <input type="hidden" name="status" value="{{ \App\Enums\EventTrooperStatus::CANCELLED->value }}" />
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fa fa-fw fa-times me-1"></i>
                        Cancel
                    </button>
                </form>
            @elseif($event->hasLimits() && $event_trooper->canReSignUp(Auth::user()))
                <form hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                      hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                      hx-select="#shift-container-{{ $event_trooper->event_shift->id }}"
                      hx-target="#shift-container-{{ $event_trooper->event_shift->id }}"
                      hx-swap="outerHTML">
                    @csrf
                    <input type="hidden" name="resign_up" value="1" />
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="fa fa-fw fa-redo me-1"></i>
                        Re-Sign Up
                    </button>
                </form>
            @else
                <span class="{{ $event_trooper->status->color() }}">
                    {{ to_title($event_trooper->status->name) }}
                    <span class="d-none d-md-inline">
                        {!! $event_trooper->status->iconTag() !!}
                    </span>
                </span>
                @if($event_trooper->status === \App\Enums\EventTrooperStatus::PENDING)
                    <br />
                    <i class="small text-muted">
                        Select a costume, or mark attending without one, to confirm your spot.
                    </i>
                @endif
            @endif

            @if(
                    $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_trooper->status === \App\Enums\EventTrooperStatus::GOING
                    && $event_trooper->updated_by !== null
                )
                <br />
                <i class="small text-muted">
                    Approved by {{ $event_trooper->updated_by->display_name }}
                    @if($event_trooper->updated_at)
                        on {{ $event_trooper->updated_at->format('M j, Y g:ia') }}
                    @endif
                </i>
            @endif
        </div>
    </div>
</div>
