{{--
    "You're bringing": the friends and guests the viewer added to this shift, changed here like
    their own sign-up (their roster rows are read-only for the viewer), and the Add a Friend /
    Add a Guest buttons. Part of "Your sign-up" (pages/events/inc/my-shift-signup.blade.php);
    shown on its own, with $standalone, to someone who isn't on the shift (a moderator adding others).
--}}
@php
    $standalone = $standalone ?? false;
    $viewer = Auth::user();
    $shift_id = $event_shift->id;

    $my_friends = $event_shift->event_troopers->filter(
        fn ($event_trooper) => $event_trooper->added_by_trooper_id === $viewer->id && $event_trooper->trooper_id !== $viewer->id
    );
    $my_guests = $event_shift->event_guests->filter(fn ($event_guest) => $event_guest->added_by_trooper_id === $viewer->id);

    //  only adults add others, once they're going (moderators any time), with the mission brief acknowledged
    $may_add_others = $event->canModerateIfClosed($can_moderate)
        || ($viewer->is_adult && $event_shift->is_open && $has_required_mission_brief_ack);
    $can_add_friend = $may_add_others
        && ($can_moderate || $event_shift->isGoing($viewer))
        && ($event->canModerateIfClosed($can_moderate) || ($event->friends_allowed !== 0 && $event_shift->hasRemainingFriendSlots($viewer)));
    $can_add_guest = $may_add_others
        && ($can_moderate || $event_shift->isGoing($viewer))
        && $event->guests_allowed !== 0
        && $event_shift->hasRemainingGuestSlots($viewer);
    $has_people = $my_friends->isNotEmpty() || $my_guests->isNotEmpty();
@endphp
@if($has_people || $can_add_friend || $can_add_guest)
    <div class="my-shift-bringing {{ $standalone ? ($has_people ? 'my-shift-signup' : 'my-shift-bringing--loose') : '' }}"
         id="my-shift-bringing-{{ $shift_id }}">
        @if($has_people)
            <div class="fw-semibold mb-2">
                <i class="fa fa-fw fa-user-group me-1"></i>
                {{ __('events.You\'re bringing') }}
            </div>
            @foreach($my_friends as $event_trooper)
                @php
                    $friend_status = $event_trooper->status;
                @endphp
                <div class="my-shift-bringing__person"
                     id="my-shift-friend-{{ $event_trooper->id }}">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <a href="{{ route('service-records.trooper', ['trooper' => $event_trooper->trooper->id]) }}">
                            {{ $event_trooper->trooper->display_name }}
                        </a>
                        <x-sign-up-status :status="$friend_status" />
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            @include('pages.events.inc.trooper-costume-controls', ['event_trooper' => $event_trooper, 'labelled' => false, 'read_only' => false])
                        </div>
                        <div class="col-12 col-md-6 text-md-end align-self-end my-shift-signup__actions">
                            @include('pages.events.inc.trooper-status-controls', ['event_trooper' => $event_trooper, 'read_only' => false, 'show_status' => false])
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach($my_guests as $event_guest)
                @php
                    $guest_status = $event_guest->status;
                @endphp
                <div class="my-shift-bringing__person"
                     id="my-shift-guest-{{ $event_guest->id }}">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-6 d-flex flex-wrap align-items-center gap-2">
                            <span>{{ $event_guest->name }}</span>
                            <span class="badge text-bg-secondary">{{ __('events.Guest') }}</span>
                            <x-sign-up-status :status="$guest_status" />
                        </div>
                        <div class="col-12 col-md-6 text-md-end my-shift-signup__actions">
                            @if($event_shift->is_open && $event_guest->canUpdateStatus($viewer))
                                @php
                                    //  manual-selection events put a returning guest on stand-by for command staff to pick
                                    $again_status = $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                                        ? \App\Enums\EventGuestStatus::STAND_BY
                                        : \App\Enums\EventGuestStatus::GOING;
                                    $is_cancelled = $guest_status === \App\Enums\EventGuestStatus::CANCELLED;
                                @endphp
                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
                                    <form hx-post="{{ route('events.guest-update-htmx', compact('event_guest')) }}"
                                          @if(!$is_cancelled)
                                              hx-confirm="{{ __('events.Cancel :name\'s sign-up for this shift?', ['name' => $event_guest->name]) }}"
                                          @endif
                                          hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
                                          hx-select="#shift-container-{{ $shift_id }}"
                                          hx-target="#shift-container-{{ $shift_id }}"
                                          hx-swap="outerHTML">
                                        @csrf
                                        <input type="hidden"
                                               name="status"
                                               value="{{ $is_cancelled ? $again_status->value : \App\Enums\EventGuestStatus::CANCELLED->value }}" />
                                        @if($is_cancelled)
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fa fa-fw fa-redo me-1"></i>
                                                {{ __('events.Sign up again') }}
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-fw fa-times me-1"></i>
                                                {{ __('events.Cancel') }}
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        @if($can_add_friend || $can_add_guest)
            <div class="my-shift-bringing__add">
                @if($can_add_friend)
                    <div id="friend-signup-action-{{ $shift_id }}"
                         class="shift-signup-secondary">
                        <div class="shift-friend-trigger">
                            <button class="btn btn-sm btn-outline-info text-start text-md-center"
                                    hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-friend-' . $shift_id, 'event' => 'trooper:selected', 'picker_mode' => App\Enums\TrooperSearchMode::FRIENDS->value]) }}"
                                    hx-target="#modal-trooper .modal-body"
                                    hx-trigger="click"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-trooper">
                                <i class="fa fa-fw fa-plus-circle me-2"></i>
                                {{ __('events.Add a Friend') }}
                            </button>
                        </div>
                        {{-- Step 2: once a trooper is picked, their costume (and organization or station when needed) and Sign Up --}}
                        <div id="add-trooper-step2-{{ $shift_id }}"
                             class="friend-signup-step"></div>
                    </div>
                    <div class="d-none"
                         hx-get="{{ route('events.add-trooper-form', compact('event_shift')) }}"
                         hx-vals="js:{trooper_id: event.detail.id}"
                         hx-trigger="trooper:selected[event.detail.property == 'add-shift-friend-{{ $shift_id }}'] from:document"
                         hx-target="#add-trooper-step2-{{ $shift_id }}"
                         hx-swap="innerHTML"
                         hx-on::before-request="document.getElementById('friend-signup-action-{{ $shift_id }}').classList.add('shift-signup-secondary--confirming'); document.querySelector('#friend-signup-action-{{ $shift_id }} .shift-friend-trigger').classList.add('d-none')"
                         hx-indicator="#transmission-bar-shift-{{ $shift_id }}"></div>
                @endif
                @if($can_add_guest)
                    {{-- a guest has no tracker account: just their name, in the same kind of box as a friend's --}}
                    <div class="shift-signup-secondary"
                         x-data="{ adding: false, name: '' }"
                         x-bind:class="adding && 'shift-signup-secondary--confirming'">
                        <button class="btn btn-sm btn-outline-info text-start text-md-center shift-signup-guest"
                                type="button"
                                x-show="!adding"
                                x-on:click="adding = true; $nextTick(() => $refs.guestName.focus())">
                            <i class="fa fa-fw fa-plus-circle me-2"></i>
                            {{ __('events.Add a Guest') }}
                        </button>
                        <div class="friend-signup-confirmation"
                             x-show="adding"
                             x-cloak>
                            <div class="small fw-semibold mb-2">
                                <i class="fa fa-fw fa-user me-1"></i>{{ __('events.Add a guest without a tracker account') }}
                            </div>
                            <div class="friend-signup-confirmation__fields">
                                <input type="text"
                                       id="guest-name-{{ $shift_id }}"
                                       name="guest_names"
                                       class="form-control form-control-sm"
                                       maxlength="255"
                                       placeholder="{{ __('events.Guest\'s name') }}"
                                       aria-label="{{ __('events.Guest\'s name') }}"
                                       x-ref="guestName"
                                       x-model="name" />
                                <button class="btn btn-sm btn-success htmx-disable"
                                        hx-post="{{ route('events.guest-signup-htmx', compact('event_shift')) }}"
                                        hx-include="#guest-name-{{ $shift_id }}"
                                        hx-select="#shift-container-{{ $shift_id }}"
                                        hx-target="#shift-container-{{ $shift_id }}"
                                        hx-swap="outerHTML"
                                        hx-indicator="#transmission-bar-shift-{{ $shift_id }}, closest .btn"
                                        x-bind:disabled="name.trim() === ''">
                                    <i class="fa fa-fw fa-plus-circle me-2"></i>{{ __('events.Add Guest') }}
                                </button>
                                <button class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        x-on:click="adding = false; name = ''">
                                    {{ __('events.Cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
