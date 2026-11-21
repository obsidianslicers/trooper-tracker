@if($my_event_trooper !== null)
    @include('pages.events.inc.my-shift-signup', ['event_trooper' => $my_event_trooper])
@elseif($event_shift->canSignUp(Auth::user()) && $has_required_mission_brief_ack)
    {{-- one step: their costume (preselected when we can guess it) and Sign Up --}}
    <div class="shift-signup-primary shift-signup-primary--form"
         x-data="{ costume: @js(!$event->requiresCostume() ? 'none' : ($signup_costume_default !== null ? (string) $signup_costume_default : '')) }">
        <span class="shift-signup-primary__label">{{ __('events.Sign up for this shift') }}</span>
        @if($event_has_org_limits)
            @if($eligible_orgs->count() > 1)
                <select id="org-picker-{{ $event_shift->id }}"
                        name="organization_id"
                        class="form-select form-select-sm">
                    <option value="">{{ __('events.-- Select Organization --') }}</option>
                    @foreach($eligible_orgs as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            @elseif($eligible_orgs->count() === 1)
                <input type="hidden"
                       id="org-picker-{{ $event_shift->id }}"
                       name="organization_id"
                       value="{{ $eligible_orgs->first()->id }}" />
                <div class="small text-muted">
                    <i class="fa fa-fw fa-building me-1"></i>
                    {{ __('events.Trooping as :organization', ['organization' => $eligible_orgs->first()->name]) }}
                </div>
            @endif
        @endif
        @if($shift_uses_stations)
            <select id="station-picker-{{ $event_shift->id }}"
                    name="event_shift_station_id"
                    class="form-select form-select-sm">
                <option value="">{{ __('events.-- Select Station --') }}</option>
                @foreach($station_options as $station_id => $station_name)
                    <option value="{{ $station_id }}">{{ $station_name }}</option>
                @endforeach
            </select>
        @endif
        @if($event->requiresCostume())
        <select id="costume-picker-{{ $event_shift->id }}"
                name="costume_id"
                class="form-select form-select-sm"
                aria-label="{{ __('events.Costume') }}"
                x-model="costume">
            <option value="">{{ __('events.-- Select Costume --') }}</option>
            @foreach($signup_costume_options as $costume_id => $costume_name)
                <option value="{{ $costume_id }}"
                        @selected($costume_id === $signup_costume_default)>{{ $costume_name }}</option>
            @endforeach
            <option value="none">{{ __('events.Attending without a costume') }}</option>
        </select>
        @endif
        @if(count($signup_status_options) > 1)
            <select id="status-picker-{{ $event_shift->id }}"
                    name="status"
                    class="form-select form-select-sm shift-signup-primary__status"
                    aria-label="{{ __('events.Your plans') }}">
                @foreach($signup_status_options as $status_value => $status_label)
                    <option value="{{ $status_value }}">{{ $status_label }}</option>
                @endforeach
            </select>
        @endif
        <button class="btn btn-sm btn-success text-start text-md-center htmx-disable"
                hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                hx-select="#shift-container-{{ $event_shift->id }}"
                hx-target="#shift-container-{{ $event_shift->id }}"
                hx-swap="outerHTML"
                hx-trigger="click"
                hx-include="#org-picker-{{ $event_shift->id }}, #station-picker-{{ $event_shift->id }}, #costume-picker-{{ $event_shift->id }}, #status-picker-{{ $event_shift->id }}"
                hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}, closest .btn"
                x-bind:disabled="costume === ''">
            <i class="fa fa-fw fa-plus-circle me-2"></i>
            {{ __('events.Sign Up') }}
        </button>
        <span class="small text-muted w-100 text-md-end"
              x-show="costume === ''"
              x-cloak>
            {{ __('events.Pick your costume, then press Sign Up.') }}
        </span>
    </div>
@elseif($requires_mission_brief_ack && !$has_required_mission_brief_ack)
    <span class="d-block small text-warning mb-2">
        <i class="fa fa-fw fa-triangle-exclamation me-1"></i>
        {{ __('events.Review and acknowledge the mission brief above to enable sign-ups.') }}
    </span>
@endif
{{-- only adults can signup others; requires mission brief acknowledgement when enabled --}}
@if($event->canModerateIfClosed($can_moderate) || (Auth::user()->is_adult && $event_shift->is_open && $has_required_mission_brief_ack))
    @if($event->canModerateIfClosed($can_moderate) || ($event->friends_allowed !== 0 && $event_shift->hasRemainingFriendSlots(Auth::user())))
        @if ($can_moderate || $event_shift->isGoing(Auth::user()))
            {{-- if they are a normal user and already signed up - they can sign up a friend --}}
            {{-- or they are a moderator - they can sign up a friend --}}
            <div id="friend-signup-action-{{ $event_shift->id }}"
                 class="shift-signup-secondary">
                <div class="shift-friend-trigger">
                    <button class="btn btn-sm btn-outline-info text-start text-md-center"
                            hx-get="{{ route('pickers.trooper', ['property' => 'add-shift-friend-' . $event_shift->id, 'event' => 'trooper:selected', 'picker_mode' => App\Enums\TrooperSearchMode::FRIENDS->value]) }}"
                            hx-target="#modal-trooper .modal-body"
                            hx-trigger="click"
                            data-bs-toggle="modal"
                            data-bs-target="#modal-trooper">
                        <i class="fa fa-fw fa-plus-circle me-2"></i>
                        {{ __('events.Add a Friend') }}
                    </button>
                </div>
                {{-- Step 2: once a trooper is picked, their costume (and organization or station when needed) and Sign Up --}}
                <div id="add-trooper-step2-{{ $event_shift->id }}"
                     class="friend-signup-step"></div>
            </div>
            <div class="d-none"
                 hx-get="{{ route('events.add-trooper-form', compact('event_shift')) }}"
                 hx-vals="js:{trooper_id: event.detail.id}"
                 hx-trigger="trooper:selected[event.detail.property == 'add-shift-friend-{{ $event_shift->id }}'] from:document"
                 hx-target="#add-trooper-step2-{{ $event_shift->id }}"
                 hx-swap="innerHTML"
                 hx-on::before-request="document.getElementById('friend-signup-action-{{ $event_shift->id }}').classList.add('shift-signup-secondary--confirming'); document.querySelector('#friend-signup-action-{{ $event_shift->id }} .shift-friend-trigger').classList.add('d-none')"
                 hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}"></div>
        @endif
    @endif
    @if($event->guests_allowed !== 0 && $event_shift->hasRemainingGuestSlots(Auth::user()))
        @if($event_shift->isGoing(Auth::user()) || $can_moderate)
            {{-- if they are a normal user and already signed up - they can sign up a guest --}}
            {{-- or they are a moderator - they can sign up a guest --}}
            <button class="btn btn-sm btn-outline-info text-start text-md-center shift-signup-guest"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-guest-{{ $event_shift->id }}">
                <i class="fa fa-fw fa-plus-circle me-2"></i>
                {{ __('events.Add a Guest') }}
            </button>

            <div class="modal fade"
                 id="modal-guest-{{ $event_shift->id }}"
                 hx-on:event-shift-guest-added="bootstrap.Modal.getInstance(this)?.hide()"
                 tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <b class="modal-title">
                                {{ __('events.Add a Guest(s)') }}
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
                                                  :placeholder="__('events.Guest Names (one per line)')" />
                                    <x-input-help>
                                        {{ __('events.Enter the name of your guest(s). One per line.') }}
                                    </x-input-help>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-primary"
                                    data-bs-dismiss="modal"
                                    hx-post="{{ route('events.guest-signup-htmx', compact('event_shift')) }}"
                                    hx-include="#modal-guest-{{ $event_shift->id }} textarea[name='guest_names']"
                                    hx-select="#shift-container-{{ $event_shift->id }}"
                                    hx-target="#shift-container-{{ $event_shift->id }}"
                                    hx-swap="outerHTML"
                                    hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}">
                                {{ __('events.Save Guests') }}
                            </button>
                            <button type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('events.Cancel') }}</button>
                        </div>
                    </div>
                </div>
            </div>

        @endif
    @endif
@endif
