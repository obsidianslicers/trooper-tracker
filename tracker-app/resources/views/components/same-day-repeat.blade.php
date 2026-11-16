{{--
    On the service record, beside a shift that didn't add a troop under "one troop per day"
    (an earlier shift of the same event that day already counted). Renders nothing otherwise.
--}}
@props(['event_trooper'])

@if($event_trooper?->is_same_day_repeat)
    <span {{ $attributes->class(['badge rounded-pill not-counted-pill']) }}
          title="{{ \App\Support\OneTroopPerDay::REPEAT_HINT }}">
        <i class="fa fa-fw fa-calendar-day"></i>
        {{ \App\Support\OneTroopPerDay::REPEAT_LABEL }}
    </span>
@endif
