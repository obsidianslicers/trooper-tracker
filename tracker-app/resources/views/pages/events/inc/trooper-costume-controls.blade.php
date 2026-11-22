{{--
    A sign-up's station, organization, costume and backup costume: pickers when $event_trooper can
    be changed by the viewer, otherwise what was picked. A roster row (pages/events/inc/trooper.blade.php)
    shows what was picked ($read_only), and its Edit area the pickers, with $labelled for labels
    above them. The backup costume picker waits behind "Add a backup costume" until one is set.
--}}
@php
    $labelled = $labelled ?? false;
    $read_only = $read_only ?? false;
    $can_change = !$read_only && $event_trooper->canUpdateCostume(Auth::user());
    $shift_target = '#shift-container-' . $event_trooper->event_shift->id;
    $with_costumes = $event->requiresCostume();
@endphp
@if($event_shift->usesStations())
    @if($can_change)
        @if($labelled)
            <label class="form-label small text-muted mb-1">{{ __('events.Station') }}</label>
        @endif
        <x-input-select :property="'event_shift_station_id'"
                        :options="$event_shift->event_shift_stations->pluck('name', 'id')->toArray()"
                        :value="$event_trooper->event_shift_station_id"
                        :placeholder="__('events.-- Select Station --')"
                        hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                        hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                        hx-select="{{ $shift_target }}"
                        hx-target="{{ $shift_target }}"
                        hx-swap="outerHTML"
                        class="form-select-sm mt-2 mt-md-0 mb-2" />
    @elseif($event_trooper->event_shift_station)
        <div class="small text-muted mb-1">
            <i class="fa fa-fw fa-location-dot me-1"></i>
            {{ $event_trooper->event_shift_station->name }}
        </div>
    @endif
@endif
@if($can_change)
    @php
        $eligible_orgs_for_change = $event_trooper->trooper->eligibleOrgsForEvent($event);
        $limited_org_ids_for_change = $event->event_organizations
            ->filter(fn ($o) => $o->troopers_allowed !== null || $o->handlers_allowed !== null)
            ->pluck('organization_id')
            ->toArray();
        $show_org_picker = $eligible_orgs_for_change->whereIn('id', $limited_org_ids_for_change)->isNotEmpty();
    @endphp
    @if($show_org_picker && $eligible_orgs_for_change->count() > 1)
        @if($labelled)
            <label class="form-label small text-muted mb-1">{{ __('events.Organization') }}</label>
        @endif
        <x-input-select :property="'organization_id'"
                        :options="$eligible_orgs_for_change->pluck('name', 'id')->toArray()"
                        :value="$event_trooper->organization_id"
                        :placeholder="__('events.-- Select Organization --')"
                        hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                        hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                        hx-select="{{ $shift_target }}"
                        hx-target="{{ $shift_target }}"
                        hx-swap="outerHTML"
                        class="form-select-sm mt-2 mt-md-0" />
    @elseif($show_org_picker && $eligible_orgs_for_change->count() === 1)
        <div class="small text-muted">
            <i class="fa fa-fw fa-building me-1"></i>
            {{ __('events.Trooping as :organization', ['organization' => $eligible_orgs_for_change->first()->name]) }}
        </div>
    @endif
    @if($with_costumes)
    @php
        $costume_options = ['none' => __('events.Attending without a costume')] + $event_trooper->costumes;
    @endphp
    @if($labelled)
        <label class="form-label small text-muted mb-1 {{ $show_org_picker ? 'mt-2' : '' }}">{{ __('events.Costume') }}</label>
    @endif
    <x-input-select :property="'costume_id'"
                    :options="$costume_options"
                    :value="$event_trooper->is_attending_without_costume ? 'none' : $event_trooper->costume_id"
                    :placeholder="__('events.-- Select Costume --')"
                    hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                    hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                    hx-select="{{ $shift_target }}"
                    hx-target="{{ $shift_target }}"
                    hx-swap="outerHTML"
                    class="form-select-sm mt-2 mt-md-0" />
    <div x-data="{ backup: @js($event_trooper->backup_costume_id !== null) }">
        <button type="button"
                class="btn btn-sm btn-outline-secondary btn-secondary-action mt-2"
                x-show="!backup"
                @if($event_trooper->backup_costume_id !== null) x-cloak @endif
                x-on:click="backup = true">
            {{ __('events.Add a backup costume') }}
        </button>
        <div x-show="backup"
             @if($event_trooper->backup_costume_id === null) x-cloak @endif>
            <label class="form-label small text-muted mb-1 mt-2">{{ __('events.Backup costume (optional)') }}</label>
            <x-input-select :property="'backup_costume_id'"
                            :options="$event_trooper->costumes"
                            :value="$event_trooper->backup_costume_id"
                            :placeholder="__('events.-- None --')"
                            hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                            hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                            hx-select="{{ $shift_target }}"
                            hx-target="{{ $shift_target }}"
                            hx-swap="outerHTML"
                            class="form-select-sm" />
        </div>
    </div>
    @endif
@elseif($with_costumes)
    @if($event_trooper->costume === null)
        @if($event_trooper->is_handler)
            {{ __('events.Handler') }}
        @elseif($event_trooper->is_attending_without_costume)
            <i class="small text-muted">
                {{ __('events.Attending without a costume') }}
            </i>
        @else
            <i class="small text-muted">
                {{ __('events.No costume selected') }}
            </i>
        @endif
    @else
        <b>
            {{ $event_trooper->costume->name }}
        </b>
        <br />
        <x-planned-org-credit :organizations="$event_trooper->costume_organizations"
                               :planned="$event->status !== \App\Enums\EventStatus::CLOSED" />
    @endif
    @if($event_trooper->backup_costume != null)
        <div class="small text-muted">
            {{ __('events.Backup: :costume', ['costume' => $event_trooper->backup_costume->name]) }}
        </div>
    @endif
@endif
