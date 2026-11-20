{{-- Add a Friend, step 2: sign up the trooper picked in the modal in one go, like the Sign Up form --}}
<div class="friend-signup-confirmation"
     x-data="{ costume: @js($costume_default !== null ? (string) $costume_default : ''), station: '' }">
    <div class="small fw-semibold mb-2">
        <i class="fa fa-fw fa-user me-1"></i>{{ __('events.Sign up :name', ['name' => $trooper->display_name]) }}
    </div>
    <div class="friend-signup-confirmation__fields">
        @if($eligible_orgs->count() > 1)
            <select id="add-org-confirm-{{ $event_shift->id }}"
                    name="organization_id"
                    class="form-select form-select-sm"
                    aria-label="{{ __('events.Organization') }}">
                @foreach($eligible_orgs as $org)
                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                @endforeach
            </select>
        @elseif($eligible_orgs->count() === 1)
            <input type="hidden"
                   id="add-org-confirm-{{ $event_shift->id }}"
                   name="organization_id"
                   value="{{ $eligible_orgs->first()->id }}" />
            <div class="small text-muted w-100">
                <i class="fa fa-fw fa-building me-1"></i>
                {{ __('events.Trooping as :organization', ['organization' => $eligible_orgs->first()->name]) }}
            </div>
        @endif
        @if(count($station_options) > 0)
            <select id="friend-station-picker-{{ $event_shift->id }}"
                    name="event_shift_station_id"
                    class="form-select form-select-sm"
                    aria-label="{{ __('events.Station') }}"
                    x-model="station">
                <option value="">{{ __('events.-- Select Station --') }}</option>
                @foreach($station_options as $station_id => $station_name)
                    <option value="{{ $station_id }}">{{ $station_name }}</option>
                @endforeach
            </select>
        @endif
        <select id="friend-costume-picker-{{ $event_shift->id }}"
                name="costume_id"
                class="form-select form-select-sm"
                aria-label="{{ __('events.Costume') }}"
                x-model="costume">
            <option value="">{{ __('events.-- Select Costume --') }}</option>
            @foreach($costume_options as $costume_id => $costume_name)
                <option value="{{ $costume_id }}"
                        @selected($costume_id === $costume_default)>{{ $costume_name }}</option>
            @endforeach
            <option value="none">{{ __('events.Attending without a costume') }}</option>
        </select>
        <button class="btn btn-sm btn-success htmx-disable"
                hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                hx-vals='{"trooper_id": {{ $trooper->id }}}'
                hx-include="#add-org-confirm-{{ $event_shift->id }}, #friend-station-picker-{{ $event_shift->id }}, #friend-costume-picker-{{ $event_shift->id }}"
                hx-select="#shift-container-{{ $event_shift->id }}"
                hx-target="#shift-container-{{ $event_shift->id }}"
                hx-swap="outerHTML"
                hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}, closest .btn"
                x-bind:disabled="costume === ''{{ count($station_options) > 0 ? " || station === ''" : '' }}">
            <i class="fa fa-fw fa-plus-circle me-2"></i>{{ __('events.Sign Up') }}
        </button>
        <button class="btn btn-sm btn-outline-secondary"
                type="button"
                onclick="document.getElementById('add-trooper-step2-{{ $event_shift->id }}').innerHTML=''; document.getElementById('friend-signup-action-{{ $event_shift->id }}').classList.remove('shift-signup-secondary--confirming'); document.querySelector('#friend-signup-action-{{ $event_shift->id }} .shift-friend-trigger').classList.remove('d-none')">
            {{ __('events.Cancel') }}
        </button>
    </div>
    <div class="small text-muted mt-2"
         x-show="costume === ''"
         x-cloak>
        {{ __('events.Pick their costume, then press Sign Up.') }}
    </div>
</div>
