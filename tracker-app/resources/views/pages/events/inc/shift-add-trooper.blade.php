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
@if($my_event_trooper === null)
    {{-- not on the shift themselves (a moderator adding others): who they added, and the Add buttons --}}
    @include('pages.events.inc.my-shift-bringing', ['standalone' => true])
@endif
