{{--
    "One troop per day", at the shift itself: before signing up for a second shift on the same
    day (a warning beside the Sign Up button), and afterwards on each shift that shares the troop.
--}}
@php($same_day_shifts = $event->sameDayShiftsFor($event_shift, Auth::user()))
@if($same_day_shifts->isNotEmpty())
    @php($other_times = $same_day_shifts->map(fn ($shift) => $shift->compact_time_display)->join(', ', ' and '))
    @php($day = $event_shift->shift_starts_at->format('l, M j'))
    @if($event->isOnShift($event_shift, Auth::user()))
        <div class="same-day-notice d-flex align-items-baseline gap-2 mt-3"
             role="note">
            <i class="fa fa-fw fa-calendar-day flex-shrink-0"></i>
            <span>
                You're also signed up for {{ $other_times }} on {{ $day }}.
                <strong>Together these shifts count as one troop.</strong>
            </span>
        </div>
    @elseif($event_shift->is_open)
        <div class="same-day-notice same-day-notice--warning d-flex align-items-baseline gap-2 mt-3"
             role="note">
            <i class="fa fa-fw fa-triangle-exclamation flex-shrink-0"></i>
            <span>
                You're already signed up for {{ $other_times }} on {{ $day }}.
                <strong>This shift won't add another troop:</strong> shifts on the same day count as one.
            </span>
        </div>
    @endif
@endif
