{{--
    Marks an event that doesn't count toward troop totals, the same way everywhere.
    pill:    a short label for headers and titles ("Doesn't count").
    icon:    the pill's icon alone, for tight spots like calendar days.
    notice:  the full sentence, as an info box (event page, event cards).
    Renders nothing for an event that counts.
--}}
@props(['event', 'variant' => 'notice'])

@if(!$event->isCounted())
    @if($variant === 'icon')
        <span {{ $attributes->class(['not-counted-icon']) }}
              title="{{ $event->notCountedNotice() }}">
            <i class="fa fa-fw fa-circle-info"
               aria-hidden="true"></i>
            <span class="visually-hidden">{{ $event->notCountedNotice() }}</span>
        </span>
    @elseif($variant === 'pill')
        <span {{ $attributes->class(['badge rounded-pill not-counted-pill']) }}
              title="{{ $event->notCountedNotice() }}">
            <i class="fa fa-fw fa-circle-info"></i>
            Doesn't count
        </span>
    @else
        <div {{ $attributes->class(['not-counted-notice d-flex align-items-baseline gap-2']) }}
             role="note">
            <i class="fa fa-fw fa-circle-info flex-shrink-0"></i>
            <span>{{ $event->notCountedNotice() }}</span>
        </div>
    @endif
@endif
