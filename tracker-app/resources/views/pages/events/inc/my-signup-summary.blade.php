{{--
    "Your sign-up" near the top of the event page: where the trooper stands on each shift, with a
    link down to their row in that shift's roster (opening its Edit), or a Sign up
    link that jumps to the roster. On phones only the bar along the bottom shows it.
    Every shift change sends this again out of band (pages/events/inc/shift-container.blade.php),
    so it stays right without a reload.
--}}
@php
    $my_signups = $event->signUpsFor(Auth::user());
    $has_open_shift = $event->event_shifts->contains(fn ($event_shift) => $event_shift->is_open);
    $my_active_signups = $my_signups->filter(fn ($event_trooper) => $event_trooper->status !== \App\Enums\EventTrooperStatus::CANCELLED);
    $multiple_shifts = $event->event_shifts->count() > 1;
    // Sign up opens these shifts' cards if they're collapsed (resources/js/custom/hash-link-collapse.js)
    $open_shift_cards = $event->event_shifts
        ->filter(fn ($event_shift) => $event_shift->is_open)
        ->map(fn ($event_shift) => '#shift-card-'.$event_shift->id)
        ->implode(',');
@endphp
<div id="my-signup-summary"
     @if($oob ?? false) hx-swap-oob="true" @endif>
    @if($my_signups->isNotEmpty())
        <div class="my-signup-summary card mb-3 d-none d-md-block">
            <div class="card-body py-2">
                <div class="fw-semibold mb-1">
                    {{ __('events.Your sign-up') }}
                </div>
                {{-- one grid for every shift (each row is a subgrid), so the times, statuses and
                     costumes line up from one shift to the next; on phones each shift takes two lines --}}
                <ul class="my-signup-summary__list list-unstyled mb-0 {{ $multiple_shifts ? 'my-signup-summary__list--shifts' : '' }}">
                    @foreach($my_signups as $event_trooper)
                        <li class="my-signup-summary__row">
                            @if($multiple_shifts)
                                <span class="my-signup-summary__time text-muted small">{{ $event_trooper->event_shift->short_time_display }}</span>
                            @endif
                            <x-sign-up-status :status="$event_trooper->status" class="my-signup-summary__status fw-semibold" />
                            <span class="my-signup-summary__costume">
                                @if(!$event->requiresCostume())
                                    {{-- an event type troopers don't wear a costume to --}}
                                @elseif($event_trooper->costume !== null)
                                    {{ $event_trooper->costume->name }}
                                @elseif($event_trooper->is_attending_without_costume)
                                    {{ __('events.Attending without a costume') }}
                                @elseif($event_trooper->is_handler)
                                    {{ __('events.Handler') }}
                                @else
                                    <i class="text-muted">{{ __('events.No costume picked yet') }}</i>
                                @endif
                                @if($event_trooper->backup_costume !== null)
                                    <span class="d-block text-muted small">
                                        {{ __('events.Backup: :costume', ['costume' => $event_trooper->backup_costume->name]) }}
                                    </span>
                                @endif
                            </span>
                            <span class="my-signup-summary__change">
                                @if($event_trooper->event_shift->is_open)
                                    <a href="#roster-row-{{ $event_trooper->id }}" data-edit-row="{{ $event_trooper->id }}"
                                       class="small">
                                        {{ __('events.Change') }}
                                    </a>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif($has_open_shift)
        <div class="my-signup-summary card mb-3 d-none d-md-block">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <span>{{ __('events.You\'re not signed up for this event yet.') }}</span>
                <a href="#mission-roster"
                   data-expand="{{ $open_shift_cards }}"
                   class="btn btn-sm btn-outline-success ms-auto">
                    {{ __('events.Sign up') }}
                </a>
            </div>
        </div>
    @endif

    @if($has_open_shift)
        {{-- phones: the same answer along the bottom, wherever they've scrolled to --}}
        <div class="my-signup-bar d-md-none">
            @if($my_active_signups->isNotEmpty())
                @php($first = $my_active_signups->first())
                <span class="my-signup-bar__status">
                    <x-sign-up-status :status="$first->status" class="fw-semibold" />
                    @if($my_active_signups->count() > 1)
                        <span class="text-muted small">
                            {{ trans_choice('events.+ :count more shift|+ :count more shifts', $my_active_signups->count() - 1) }}
                        </span>
                    @endif
                </span>
                <a href="#roster-row-{{ $first->id }}"
                   class="btn btn-sm btn-outline-primary">
                    {{ __('events.Your sign-up') }}
                </a>
            @else
                <span class="my-signup-bar__status text-muted">{{ __('events.Not signed up') }}</span>
                <a href="#mission-roster"
                   data-expand="{{ $open_shift_cards }}"
                   class="btn btn-sm btn-outline-success">
                    {{ __('events.Sign up') }}
                </a>
            @endif
        </div>
    @endif
</div>
