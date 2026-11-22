{{-- Add a friend, step 2: once a trooper is picked in the modal, their choices and Confirm, in the
     same box as your own Sign up (pages/events/inc/shift-add-trooper.blade.php) --}}
<div class="shift-signup-primary shift-signup-primary--form"
     x-data="{ costume: @js(!$event_shift->event->requiresCostume() ? 'none' : ($costume_default !== null ? (string) $costume_default : '')), station: '' }">
    <span class="shift-signup-primary__label">{{ __('events.Sign up :name', ['name' => $trooper->display_name]) }}</span>
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
        <span class="small text-muted w-100">{{ __('events.Trooping as :organization', ['organization' => $eligible_orgs->first()->name]) }}</span>
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
    @if($event_shift->event->requiresCostume())
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
    @endif
    <div class="shift-signup__confirm">
        <button class="btn btn-sm btn-success htmx-disable"
                hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                hx-vals='{"trooper_id": {{ $trooper->id }}}'
                hx-include="#add-org-confirm-{{ $event_shift->id }}, #friend-station-picker-{{ $event_shift->id }}, #friend-costume-picker-{{ $event_shift->id }}"
                hx-select="#shift-container-{{ $event_shift->id }}"
                hx-target="#shift-container-{{ $event_shift->id }}"
                hx-swap="outerHTML"
                hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}, closest .btn"
                x-bind:disabled="costume === ''{{ count($station_options) > 0 ? " || station === ''" : '' }}">
            {{ __('events.Confirm') }}
        </button>
        <button class="btn btn-link btn-sm text-muted"
                type="button"
                onclick="document.getElementById('add-trooper-step2-{{ $event_shift->id }}').innerHTML = ''; document.getElementById('friend-signup-action-{{ $event_shift->id }}').classList.remove('d-none')">
            {{ __('events.Never mind') }}
        </button>
        @if($event_shift->event->requiresCostume())
            <span class="small text-muted"
                  x-show="costume === ''"
                  x-cloak>
                {{ __('events.Pick their costume, then press Confirm.') }}
            </span>
        @endif
    </div>
</div>
