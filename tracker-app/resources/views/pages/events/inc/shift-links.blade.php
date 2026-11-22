{{--
    The shift's quieter actions, as links under the roster: Add a friend and Add a guest (once
    you're going, or any time for moderators), Add to calendar once you're signed up, and the
    shift's CSV for moderators. The people you add land in the roster, where their rows get an
    Edit link like yours.
--}}
@php
    $viewer = Auth::user();
    $shift_id = $event_shift->id;

    //  only adults add others, once they're going (moderators any time), with the mission brief acknowledged
    $has_brief_ack = !$event->require_mission_brief_ack || $event->hasMissionBriefAcknowledgementFor($viewer);
    $may_add_others = $event->canModerateIfClosed($can_moderate)
        || ($viewer->is_adult && $event_shift->is_open && $has_brief_ack);
    $can_add_friend = $may_add_others
        && ($can_moderate || $event_shift->isGoing($viewer))
        && ($event->canModerateIfClosed($can_moderate) || ($event->friends_allowed !== 0 && $event_shift->hasRemainingFriendSlots($viewer)));
    $can_add_guest = $may_add_others
        && ($can_moderate || $event_shift->isGoing($viewer))
        && $event->guests_allowed !== 0
        && $event_shift->hasRemainingGuestSlots($viewer);

    $my_sign_up = $event_shift->signUpFor($viewer);
    $show_calendar = $my_sign_up !== null && $my_sign_up->status !== \App\Enums\EventTrooperStatus::CANCELLED;
@endphp
@if($can_add_friend || $can_add_guest || $show_calendar || $can_moderate)
    <div class="shift-links"
         x-data="{ addingGuest: false, guestName: '' }">
        <div class="shift-links__row">
            @if($can_add_friend)
                <div id="friend-signup-action-{{ $shift_id }}">
                    <div class="shift-links__friend">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-secondary-action"
                                hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-friend-' . $shift_id, 'event' => 'trooper:selected', 'picker_mode' => App\Enums\TrooperSearchMode::FRIENDS->value]) }}"
                                hx-target="#modal-trooper .modal-body"
                                hx-trigger="click"
                                data-bs-toggle="modal"
                                data-bs-target="#modal-trooper">
                            {{ __('events.Add a friend') }}
                        </button>
                    </div>
                </div>
            @endif
            @if($can_add_guest)
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        x-on:click="addingGuest = !addingGuest; $nextTick(() => addingGuest && $refs.guestName.focus())">
                    {{ __('events.Add a guest') }}
                </button>
            @endif
            @if($show_calendar)
                @php($link = $event_shift->createCalendarLink())
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary btn-secondary-action dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        {{ __('events.Add to calendar') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" target="_blank" href="{{ $link->google() }}">{{ __('events.Google Calendar') }}</a></li>
                        <li><a class="dropdown-item" target="_blank" href="{{ $link->webOutlook() }}">{{ __('events.Outlook') }}</a></li>
                        <li><a class="dropdown-item" target="_blank" href="{{ $link->yahoo() }}">{{ __('events.Yahoo Calendar') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('events.download-shift-ics', ['event' => $event, 'event_shift' => $event_shift]) }}">{{ __('events.Apple Calendar or other (.ics file)') }}</a></li>
                    </ul>
                </div>
            @endif
            @if($can_moderate)
                <a class="btn btn-link p-0 text-muted d-none d-md-inline"
                   href="{{ route('events.download-shift-roster-csv', ['event' => $event, 'event_shift' => $event_shift]) }}">
                    {{ __('events.Download this shift as CSV') }}
                </a>
            @endif
        </div>

        @if($can_add_friend)
            {{-- once a trooper is picked in the modal: their choices and Confirm (add-trooper-form.blade.php) --}}
            <div id="add-trooper-step2-{{ $shift_id }}"
                 class="friend-signup-step"></div>
            <div class="d-none"
                 hx-get="{{ route('events.add-trooper-form', compact('event_shift')) }}"
                 hx-vals="js:{trooper_id: event.detail.id}"
                 hx-trigger="trooper:selected[event.detail.property == 'add-shift-friend-{{ $shift_id }}'] from:document"
                 hx-target="#add-trooper-step2-{{ $shift_id }}"
                 hx-swap="innerHTML"
                 hx-on::before-request="document.getElementById('friend-signup-action-{{ $shift_id }}').classList.add('d-none')"
                 hx-indicator="#transmission-bar-shift-{{ $shift_id }}"></div>
        @endif

        @if($can_add_guest)
            {{-- a guest has no tracker account: just their name, in the same box as your own Sign up --}}
            <div class="shift-signup-primary shift-signup-primary--form"
                 x-show="addingGuest"
                 x-cloak>
                <span class="shift-signup-primary__label">{{ __('events.Add a guest without a tracker account') }}</span>
                <input type="text"
                       id="guest-name-{{ $shift_id }}"
                       name="guest_names"
                       class="form-control form-control-sm"
                       maxlength="255"
                       placeholder="{{ __('events.Guest\'s name') }}"
                       aria-label="{{ __('events.Guest\'s name') }}"
                       x-ref="guestName"
                       x-model="guestName" />
                <div class="shift-signup__confirm">
                    <button class="btn btn-sm btn-success htmx-disable"
                            hx-post="{{ route('events.guest-signup-htmx', compact('event_shift')) }}"
                            hx-include="#guest-name-{{ $shift_id }}"
                            hx-select="#shift-container-{{ $shift_id }}"
                            hx-target="#shift-container-{{ $shift_id }}"
                            hx-swap="outerHTML"
                            hx-indicator="#transmission-bar-shift-{{ $shift_id }}, closest .btn"
                            x-bind:disabled="guestName.trim() === ''">
                        {{ __('events.Confirm') }}
                    </button>
                    <button class="btn btn-link btn-sm text-muted"
                            type="button"
                            x-on:click="addingGuest = false; guestName = ''">
                        {{ __('events.Never mind') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
@endif
