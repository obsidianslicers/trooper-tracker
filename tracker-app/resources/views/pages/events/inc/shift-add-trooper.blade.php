@php
    $requires_mission_brief_ack = $event->require_mission_brief_ack;
    $has_required_mission_brief_ack = !$requires_mission_brief_ack || $event->hasMissionBriefAcknowledgementFor(Auth::user());
@endphp

@if($event_shift->canSignUp(Auth::user()) && $has_required_mission_brief_ack)
    <button class="btn btn-sm btn-outline-success text-start text-md-center htmx-disable"
            hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
            hx-select="#shift-container-{{ $event_shift->id }}"
            hx-target="#shift-container-{{ $event_shift->id }}"
            hx-swap="outerHTML"
            hx-trigger="click"
            hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}, closest .btn">
        <i class="fa fa-fw fa-plus-circle me-2"></i>
        @if(Auth::user()->is_handler)
            Handler Sign Up
        @else
            Trooper Sign Up
        @endif
    </button>
@elseif($requires_mission_brief_ack && !$has_required_mission_brief_ack)
    <span class="d-block small text-warning mb-2">
        <i class="fa fa-fw fa-triangle-exclamation me-1"></i>
        Review and acknowledge the mission brief above to enable sign-ups.
    </span>
@endif
{{-- only adults can signup others --}}
@if(Auth::user()->is_adult && $event_shift->is_open)
    @if($event->friends_allowed !== 0 && $event_shift->hasRemainingFriendSlots(Auth::user()))
        @if ($event_shift->isGoing(Auth::user()) || $can_moderate)
            {{-- if they are a normal user and already signed up - they can sign up a friend --}}
            {{-- or they are a moderator - they can sign up a friend --}}
            <button class="btn btn-sm btn-outline-info text-start text-md-center"
                    hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-trooper-' . $event_shift->id, 'event' => 'trooper:selected', 'picker_mode' => App\Enums\TrooperPickerMode::FRIENDS->value]) }}"
                    hx-target="#modal-trooper .modal-body"
                    hx-trigger="click"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-trooper">
                <i class="fa fa-fw fa-plus-circle me-2"></i>
                Add a Trooper
            </button>
            {{-- TO CATCH THE MODAL TROOPER PICKER PICK --}}
            <div class="d-none"
                 hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                 hx-vals="js:{trooper_id: event.detail.id}"
                 hx-trigger="trooper:selected[event.detail.property == 'add-shift-trooper-{{ $event_shift->id }}'] from:document"
                 hx-select="#shift-container-{{ $event_shift->id }}"
                 hx-target="#shift-container-{{ $event_shift->id }}"
                 hx-swap="outerHTML"
                 hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}"></div>
        @endif
    @endif
    @if($event->guests_allowed !== 0 && $event_shift->hasRemainingGuestSlots(Auth::user()))
        @if($event_shift->isGoing(Auth::user()) || $can_moderate)
            {{-- if they are a normal user and already signed up - they can sign up a guest --}}
            {{-- or they are a moderator - they can sign up a guest --}}
            <button class="btn btn-sm btn-outline-info text-start text-md-center"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-guest-{{ $event_shift->id }}">
                <i class="fa fa-fw fa-plus-circle me-2"></i>
                Add a Guest
            </button>

            <div class="modal fade"
                 id="modal-guest-{{ $event_shift->id }}"
                 hx-on:event-shift-guest-added="bootstrap.Modal.getInstance(this).hide()"
                 tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <b class="modal-title">
                                Add a Guest(s)
                            </b>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-1">
                                <div class="col-12 text-start">
                                    <x-input-text :property="'guest_names'"
                                                  :multiline="true"
                                                  placeholder="Guest Names (one per line)" />
                                    <x-input-help>
                                        Enter the name of your guest(s). One per line.
                                    </x-input-help>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-primary"
                                    hx-post="{{ route('events.guest-signup-htmx', compact('event_shift')) }}"
                                    hx-include="#modal-guest-{{ $event_shift->id }} textarea[name='guest_names']"
                                    hx-select="#shift-container-{{ $event_shift->id }}"
                                    hx-target="#shift-container-{{ $event_shift->id }}"
                                    hx-swap="outerHTML"
                                    hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}">
                                Save Guests
                            </button>
                            <button type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

        @endif
    @endif
@endif