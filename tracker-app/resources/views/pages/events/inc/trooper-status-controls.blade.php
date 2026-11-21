{{--
    A sign-up's status and what can be done with it: Going/Tentative, a Cancel button (with a
    confirmation, not a "Cancelled" option in a dropdown), Sign up again, Made It / Missed It after
    the troop, and Approve / Reject for moderators of manual-selection events. Used on roster rows
    (pages/events/inc/trooper.blade.php) and on the trooper's own "Your sign-up" panel
    (pages/events/inc/my-shift-signup.blade.php). $read_only shows only the status (their own
    roster row, which they change on the panel); moderation buttons still show.
--}}
@php
    $read_only = $read_only ?? false;
    $show_status = $show_status ?? true;
    $viewer = Auth::user();
    $status = $event_trooper->status;
    $is_self = $event_trooper->trooper_id === $viewer->id;
    $shift_id = $event_trooper->event_shift->id;
    $update_url = route('events.signup-update-htmx', compact('event_trooper'));
@endphp
@if(
        $can_moderate
        && $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
        && $event_shift->is_open
        && in_array($status, [\App\Enums\EventTrooperStatus::STAND_BY, \App\Enums\EventTrooperStatus::GOING], true)
    )
    @if($status === \App\Enums\EventTrooperStatus::STAND_BY && $event_trooper->needsCostumeBeforeGoing())
        <x-sign-up-status :status="\App\Enums\EventTrooperStatus::PENDING" :block="true" />
        <i class="small text-muted">
            {{ __('events.Select a costume, or mark attending without one, to confirm your spot.') }}
        </i>
    @else
        <div class="d-flex gap-1 justify-content-end">
            @if($status === \App\Enums\EventTrooperStatus::STAND_BY)
                <form hx-post="{{ $update_url }}"
                      hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
                      hx-select="#shift-container-{{ $shift_id }}"
                      hx-target="#shift-container-{{ $shift_id }}"
                      hx-swap="outerHTML">
                    @csrf
                    <input type="hidden"
                           name="status"
                           value="{{ \App\Enums\EventTrooperStatus::GOING->value }}" />
                    <button type="submit"
                            class="btn btn-sm btn-success">
                        <i class="fa fa-fw fa-check me-1"></i>
                        {{ __('events.Approve') }}
                    </button>
                </form>
            @endif
            @if($status === \App\Enums\EventTrooperStatus::GOING)
                <form hx-post="{{ $update_url }}"
                      hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
                      hx-select="#shift-container-{{ $shift_id }}"
                      hx-target="#shift-container-{{ $shift_id }}"
                      hx-swap="outerHTML">
                    @csrf
                    <input type="hidden"
                           name="status"
                           value="{{ \App\Enums\EventTrooperStatus::STAND_BY->value }}" />
                    <button type="submit"
                            class="btn btn-sm btn-outline-danger">
                        <i class="fa fa-fw fa-ban me-1"></i>
                        {{ __('events.Reject') }}
                    </button>
                </form>
            @endif
        </div>
    @endif
@elseif($read_only)
    <x-sign-up-status :status="$status" />
@elseif($event_shift->is_open && $status === \App\Enums\EventTrooperStatus::CANCELLED)
    @if($show_status)
        <x-sign-up-status :status="$status" :block="true" />
    @endif
    @php
        //  limited events re-queue them by sign-up time; the others just set them back to going
        $can_sign_up_again = $event->hasLimits()
            ? $event_trooper->canReSignUp($viewer)
            : $event_trooper->canUpdateStatus($viewer);
    @endphp
    @if($can_sign_up_again)
        <form hx-post="{{ $update_url }}"
              hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
              hx-select="#shift-container-{{ $shift_id }}"
              hx-target="#shift-container-{{ $shift_id }}"
              hx-swap="outerHTML">
            @csrf
            @if($event->hasLimits())
                <input type="hidden" name="resign_up" value="1" />
            @else
                <input type="hidden" name="status" value="{{ \App\Enums\EventTrooperStatus::GOING->value }}" />
            @endif
            <button type="submit" class="btn btn-sm btn-success">
                <i class="fa fa-fw fa-redo me-1"></i>
                {{ __('events.Sign up again') }}
            </button>
        </form>
    @endif
@elseif($event_shift->is_open && $event_trooper->canCancel($viewer))
    @php
        $plan_options = $event->planStatusOptions($status);
        $can_change_plans = in_array($status, \App\Enums\EventTrooperStatus::intentToGoArray(), true)
            && $event_trooper->canUpdateStatus($viewer)
            && count($plan_options) > 1;
    @endphp
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
        @if($can_change_plans)
            <x-input-select :property="'status'"
                            :options="$plan_options"
                            :value="$status->value"
                            aria-label="{{ __('events.Your plans') }}"
                            hx-post="{{ $update_url }}"
                            hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
                            hx-select="#shift-container-{{ $shift_id }}"
                            hx-target="#shift-container-{{ $shift_id }}"
                            hx-swap="outerHTML"
                            class="form-select-sm w-auto" />
        @elseif($show_status)
            <x-sign-up-status :status="$status" />
        @endif
        <form hx-post="{{ $update_url }}"
              hx-confirm="{{ $is_self ? __('events.Cancel your sign-up for this shift?') : __('events.Cancel :name\'s sign-up for this shift?', ['name' => $event_trooper->trooper->display_name]) }}"
              hx-indicator="#transmission-bar-shift-{{ $shift_id }}"
              hx-select="#shift-container-{{ $shift_id }}"
              hx-target="#shift-container-{{ $shift_id }}"
              hx-swap="outerHTML">
            @csrf
            <input type="hidden" name="status" value="{{ \App\Enums\EventTrooperStatus::CANCELLED->value }}" />
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="fa fa-fw fa-times me-1"></i>
                {{ $is_self ? __('events.Cancel my sign-up') : __('events.Cancel') }}
            </button>
        </form>
    </div>
    @if($show_status && $status === \App\Enums\EventTrooperStatus::PENDING)
        <i class="small text-muted d-block mt-1">
            {{ __('events.Select a costume, or mark attending without one, to confirm your spot.') }}
        </i>
    @endif
@elseif($event_shift->is_closed && $event_shift->shift_ends_at->isPast() && $event_trooper->isEligibleForAttendance() && ($can_moderate || $event_trooper->canMarkAttendance($viewer)))
    @if(in_array($status, [\App\Enums\EventTrooperStatus::ATTENDED, \App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND, \App\Enums\EventTrooperStatus::AWAITING_VERIFICATION], true))
        @if($show_status)
            <x-sign-up-status :status="$status" :block="true" />
        @endif
    @else
        <div class="input-group input-group-sm d-inline-flex w-auto">
            <a class="btn {{ $status === \App\Enums\EventTrooperStatus::ATTENDED ? 'btn-success' : 'btn-outline-success' }}"
               href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::ATTENDED) }}">
                {{ __('events.Made It') }}
            </a>
            <a class="btn {{ $status === \App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND ? 'btn-danger' : 'btn-outline-danger' }}"
               href="{{ $event_trooper->getAttendanceUrl(\App\Enums\EventTrooperStatus::UNABLE_TO_ATTEND) }}">
                {{ __('events.Missed It') }}
            </a>
        </div>
    @endif
@elseif($show_status)
    <x-sign-up-status :status="$status" />
    @if($status === \App\Enums\EventTrooperStatus::PENDING)
        <br />
        <i class="small text-muted">
            {{ __('events.Select a costume, or mark attending without one, to confirm your spot.') }}
        </i>
    @endif
@endif

@if(
        $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
        && $status === \App\Enums\EventTrooperStatus::GOING
        && $event_trooper->updated_by !== null
    )
    <br />
    <i class="small text-muted">
        @if($event_trooper->updated_at)
            {{ __('events.Approved by :name on :date', ['name' => $event_trooper->updated_by->display_name, 'date' => $event_trooper->updated_at->localFormat('M j, Y g:ia')]) }}
        @else
            {{ __('events.Approved by :name', ['name' => $event_trooper->updated_by->display_name]) }}
        @endif
    </i>
@endif
