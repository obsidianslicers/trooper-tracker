{{--
    "One troop per day" (tracker.count_as_one_troop_per_day), said up front on an event with more
    than one shift on the same day, so nobody signs up for two shifts expecting two troops.
    Renders nothing when the rule doesn't apply to the event.
--}}
@props(['event'])

@if($event->hasSameDayShifts())
    <div {{ $attributes->class(['same-day-notice d-flex align-items-baseline gap-2']) }}
         role="note">
        <i class="fa fa-fw fa-calendar-day flex-shrink-0"></i>
        <span>
            <strong>{{ \App\Support\OneTroopPerDay::NOTICE }}</strong>
            Two shifts on the same day add one troop to your total, not two.
            Your hours and charity still count for every shift.
        </span>
    </div>
@endif
