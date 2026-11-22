{{--
    Sign up, at the end of the roster, where the new row will land. Pressing it shows what there is
    to choose (costume, preselected when we can guess it; Going/Tentative where the event allows it;
    club or station when needed) and Confirm. With nothing to choose, Sign up signs up straight away.
    Once signed up there's nothing here: their own roster row has an Edit link instead.
--}}
@if($my_event_trooper === null && $event_shift->canSignUp(Auth::user()) && $has_required_mission_brief_ack)
    @php
        $spots_left = $event_shift->trooperSpotsLeft();
        $stand_by_place = $event_shift->standByCount() + 1;
        $is_manual = $event->status === \App\Enums\EventStatus::MANUAL_SELECTION;
        $is_full = !$is_manual && $spots_left === 0;
        $has_choices = $event->requiresCostume()
            || count($signup_status_options) > 1
            || ($event_has_org_limits && $eligible_orgs->count() > 1)
            || $shift_uses_stations;
        if ($is_manual)
        {
            $signup_hint = __('events.Command staff picks who goes from the sign-ups.');
        }
        elseif ($is_full)
        {
            $signup_hint = __('events.Full. You\'d be #:place on stand-by, and move up in order if someone going cancels.', ['place' => $stand_by_place]);
        }
        elseif ($spots_left !== null)
        {
            $signup_hint = trans_choice('events.:count spot left|:count spots left', $spots_left);
        }
        else
        {
            $signup_hint = null;
        }
        $signup_post = [
            'hx-post'      => route('events.signup-htmx', compact('event_shift')),
            'hx-select'    => '#shift-container-' . $event_shift->id,
            'hx-target'    => '#shift-container-' . $event_shift->id,
            'hx-swap'      => 'outerHTML',
            'hx-trigger'   => 'click',
            'hx-include'   => "#org-picker-{$event_shift->id}, #station-picker-{$event_shift->id}, #costume-picker-{$event_shift->id}, #status-picker-{$event_shift->id}, #backup-picker-{$event_shift->id}",
            'hx-indicator' => "#transmission-bar-shift-{$event_shift->id}, closest .btn",
        ];
    @endphp
    <div class="shift-signup"
         id="shift-signup-{{ $event_shift->id }}"
         x-data="{ open: false, backup: false, costume: @js(!$event->requiresCostume() ? 'none' : ($signup_costume_default !== null ? (string) $signup_costume_default : '')) }">
        <div class="shift-signup__start"
             x-show="!open">
            @if($has_choices)
                <button type="button"
                        class="btn btn-success shift-signup__button"
                        x-on:click="open = true; $nextTick(() => $el.closest('.shift-signup').querySelector('select')?.focus())">
                    {{ $is_full ? __('events.Join stand-by') : __('events.Sign Up') }}
                </button>
            @else
                <button type="button"
                        class="btn btn-success shift-signup__button htmx-disable"
                        @foreach($signup_post as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>
                    {{ $is_full ? __('events.Join stand-by') : __('events.Sign Up') }}
                </button>
            @endif
            @if($signup_hint)
                <span class="text-muted">{{ $signup_hint }}</span>
            @endif
        </div>

        @if($has_choices)
            <div class="shift-signup-primary shift-signup-primary--form"
                 x-show="open"
                 x-cloak>
                <span class="shift-signup-primary__label">{{ __('events.Sign up for this shift') }}</span>
                @if($event_has_org_limits)
                    @if($eligible_orgs->count() > 1)
                        <select id="org-picker-{{ $event_shift->id }}"
                                name="organization_id"
                                class="form-select form-select-sm"
                                aria-label="{{ __('events.Organization') }}">
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
                        <span class="small text-muted w-100">{{ __('events.Trooping as :organization', ['organization' => $eligible_orgs->first()->name]) }}</span>
                    @endif
                @endif
                @if($shift_uses_stations)
                    <select id="station-picker-{{ $event_shift->id }}"
                            name="event_shift_station_id"
                            class="form-select form-select-sm"
                            aria-label="{{ __('events.Station') }}">
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
                @if($event->requiresCostume() && count($signup_costume_options) > 0)
                    {{-- optional: a link until they want one, then the picker --}}
                    <div class="shift-signup__backup">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-secondary-action"
                                x-show="!backup"
                                x-on:click="backup = true; $nextTick(() => $refs.backup.focus())">
                            {{ __('events.Add a backup costume') }}
                        </button>
                        <select id="backup-picker-{{ $event_shift->id }}"
                                name="backup_costume_id"
                                class="form-select form-select-sm"
                                aria-label="{{ __('events.Backup costume (optional)') }}"
                                x-ref="backup"
                                x-show="backup"
                                x-cloak>
                            <option value="">{{ __('events.-- No backup costume --') }}</option>
                            @foreach($signup_costume_options as $costume_id => $costume_name)
                                <option value="{{ $costume_id }}">{{ __('events.Backup: :costume', ['costume' => $costume_name]) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="shift-signup__confirm">
                    <button class="btn btn-sm btn-success htmx-disable"
                            @foreach($signup_post as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach
                            x-bind:disabled="costume === ''">
                        {{ $is_full ? __('events.Confirm stand-by') : __('events.Confirm') }}
                    </button>
                    <button type="button"
                            class="btn btn-link btn-sm text-muted"
                            x-on:click="open = false">
                        {{ __('events.Never mind') }}
                    </button>
                    @if($event->requiresCostume())
                        <span class="small text-muted"
                              x-show="costume === ''"
                              x-cloak>
                            {{ __('events.Pick your costume, then press Confirm.') }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@elseif($my_event_trooper === null && $requires_mission_brief_ack && !$has_required_mission_brief_ack)
    <span class="d-block small text-warning mb-2">
        {{ __('events.Review and acknowledge the mission brief above to enable sign-ups.') }}
    </span>
@endif
