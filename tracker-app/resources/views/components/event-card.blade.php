@props(['event'])
@php($bg = $event->at_risk ? 'bg-danger' : 'bg-primary')
@php($bg = $event->is_locked ? 'bg-secondary' : $bg)

<div class="col">
    <div class="card h-100"
         data-route="{{ route('events.signup', compact('event')) }}">
        <div class="card-header {{ $bg }} d-flex align-items-center">
            <span class="p-2">
                <x-logo :storage_path="$event->organization->image_path_sm ?? ''"
                        :default_path="'img/icons/organization-32x32.png'"
                        :width="32"
                        :height="32" />
            </span>
            <span class="p-1 text-white">
                {{ $event->name }}
            </span>
        </div>

        <div class="card-body">
            <p class="card-text">
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
        </div>

        <div class="card-footer bg-secondary p-0">
            <ul class="list-group list-group-flush">
                @php($shifts_count = $event->event_shifts->count())
                @if($shifts_count > 1)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $shifts_count }} Shifts Available
                    </li>
                @endif
                @foreach($event->event_shifts as $shift)
                    <a href="{{ route('events.signup', compact('event', 'shift')) }}"
                       class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            @if($shifts_count > 1)
                                {{ $shift->shift_starts_at->format('m/d') }}
                                -
                            @endif
                            {{ $shift->shift_starts_at->format('g:i a') }}
                            -
                            {{ $shift->shift_ends_at->format('g:i a') }}
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
                                {{ $shift->event_troopers_count }} attending
                            @endif
                        </span>
                    </a>
                @endforeach
            </ul>
        </div>
    </div>
</div>