{{--
    A sign-up's station, organization, costume and backup costume: pickers when $event_trooper can
    be changed by the viewer, otherwise what was picked. Used on roster rows
    (pages/events/inc/trooper.blade.php) and on the trooper's own "Your sign-up" panel
    (pages/events/inc/my-shift-signup.blade.php), which passes $labelled for labels above the pickers.
    $read_only shows what was picked even to someone who could change it (their own roster row,
    which they change on the panel instead).
--}}
@php
    $labelled = $labelled ?? false;
    $read_only = $read_only ?? false;
    $can_change = !$read_only && $event_trooper->canUpdateCostume(Auth::user());
    $shift_target = '#shift-container-' . $event_trooper->event_shift->id;
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
    @if($labelled)
        <label class="form-label small text-muted mb-1 mt-2">{{ __('events.Backup costume (optional)') }}</label>
    @endif
    <x-input-select :property="'backup_costume_id'"
                    :options="$event_trooper->costumes"
                    :value="$event_trooper->backup_costume_id"
                    :placeholder="$labelled ? __('events.-- None --') : __('events.-- Select Backup Costume (optional) --')"
                    hx-post="{{ route('events.signup-update-htmx', compact('event_trooper')) }}"
                    hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                    hx-swap="none"
                    class="form-select-sm mt-2 {{ $labelled ? 'mt-md-0' : '' }}" />
@else
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
        <br />
        <i class="small text-muted">
            <i class="fa fa-fw fa-box-archive"></i>
            {{ $event_trooper->backup_costume->name }}
        </i>
    @endif
@endif
