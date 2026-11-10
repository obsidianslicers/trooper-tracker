{{--
    Marks an event that doesn't count toward troop totals, the same way everywhere.
    logo:    a white info icon in place of the unit logo in a colored card header.
    pill:    a short label beside the event's name ("Non-troop-credit").
    icon:    the pill's icon alone, for tight spots like calendar days.
    notice:  the full sentence, as an info box (event page, event cards).
    Renders nothing for an event that counts.
--}}
@props(['event', 'variant' => 'notice'])

@if(!$event->isCounted())
    @if($variant === 'logo')
        <i {{ $attributes->class(['fa fa-fw fa-circle-info fa-2x text-white not-counted-logo']) }}
           title="{{ $event->notCountedNotice() }}"
           aria-hidden="true"></i>
    @elseif($variant === 'icon')
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
            Non-troop-credit
        </span>
    @else
        <div {{ $attributes->class(['not-counted-notice d-flex align-items-baseline gap-2']) }}
             role="note">
            <i class="fa fa-fw fa-circle-info flex-shrink-0"></i>
            <span>{{ $event->notCountedNotice() }}</span>
        </div>
    @endif
@endif
