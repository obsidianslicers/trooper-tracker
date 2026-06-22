@php($bg = $event->at_risk ? 'bg-danger' : 'bg-primary')
@php($bg = $event->is_locked ? 'bg-secondary' : $bg)

<div class="col"
     x-show="matches($el)"
     data-event-name="{{ $event->name }}"
     data-event-hosting-organization-id="{{ $event->organization_id  }}">
    <div class="card h-100"
         data-route="{{ route('events.display', compact('event')) }}"
         x-on:click="window.location = $el.dataset.route">
        <div class="card-header {{ $bg }} d-flex align-items-center">
            <a href="{{ route('events.display', compact('event')) }}"
               class="d-block w-100">
                <span class="p-2">
                    @if($event->at_risk)
                        <i class="fa fa-fw fa-warning fa-2x pe-2 text-white"></i>
                    @else
                        <x-logo :storage_path="$event->organization->image_path_sm ?? ''"
                                :default_path="'img/icons/organization-32x32.png'"
                                :width="32"
                                :height="32" />
                    @endif
                </span>
                <span class="p-1 text-white">
                    {{ $event->name }}
                </span>
            </a>
        </div>

        <div class="card-body">
            @if($event->at_risk)
                <p class="card-text text-danger">
                    ** risk of cancellation **
                </p>
            @endif
            <p class="card-text position-relative"
               style="z-index: 2;">
                <a href="https://www.google.com/maps/search/?api=1&query={{ $event->venue_address }}"
                   target="_blank"
                   class="text-decoration-none">
                    <i class="fa fa-fw fa-location-dot me-2"></i>
                    {{ $event->venue_address }}
                </a>
            </p>
            <p class="card-text">
                <i class="fa fa-fw fa-calendar-day me-2"></i>
                {{ $event->event_start->format('D M d, Y') }}
            </p>
            <ul class="list-group list-group-flush">
                @php($shifts_count = $event->event_shifts->count())
                @if($shifts_count > 1)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $shifts_count }} Shifts Available
                    </li>
                @endif
                @foreach($event->event_shifts as $shift)
                    <a href="{{ route('events.display', compact('event')) }}#shift-{{ $shift->id }}"
                       class="list-group-item d-flex justify-content-between align-items-center position-relative"
                       style="z-index: 2;">
                        <span>
                            @if($shifts_count > 1)
                                {{ $shift->shift_starts_at->format('m/d') }}
                                -
                            @endif
                            {{ $shift->shift_starts_at->format('g:ia') }}
                            -
                            {{ $shift->shift_ends_at->format('g:ia') }}
                        </span>
                        <span class="fw-bold">
                            @if($shift->troopers_allowed != null && $shift->event_troopers_count >= $shift->troopers_allowed)
                                <span class="text-success">
                                    FULL TROOP
                                    <i class="fa fa-fw fa-check-circle ms-2"></i>
                                </span>
                            @elseif($shift->event_troopers_count == 0)
                                <span class="text-danger">
                                    NOT ENOUGH!
                                </span>
                            @else
                                {{ $shift->event_troopers_count }} going
                            @endif
                        </span>
                    </a>
                @endforeach
            </ul>
        </div>

        <template x-if="display_allowed_orgs">
            <div class="card-footer bg-secondary p-0">
                <ul class="list-group list-group-flush">
                    @foreach($event->organizations as $organization)
                        <li class="list-group-item"
                            data-event-status="{{ $organization->pivot->can_attend ?? false }}"
                            data-event-costume-organization-id="{{ $organization->id }}">
                            <x-yes-no class="me-2"
                                      :value="$organization->pivot->can_attend ?? false" />
                            {{ $organization->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </template>
    </div>
</div>