{{--
    One guest (no tracker account) on a shift's roster, laid out like a trooper's row
    (pages/events/inc/trooper.blade.php). Whoever added them gets Edit, to cancel them or sign them
    up again; moderators of manual-selection events get Approve / Reject.
--}}
@php
    $viewer = Auth::user();
    $guest_shift_id = $event_guest->event_shift->id;
    $guest_update_url = route('events.guest-update-htmx', compact('event_guest'));
    $is_cancelled = $event_guest->status === \App\Enums\EventGuestStatus::CANCELLED;
    $can_edit = $event_shift->is_open && $event_guest->canUpdateStatus($viewer);
    //  manual-selection events put a returning guest on stand-by for command staff to pick
    $again_status = $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
        ? \App\Enums\EventGuestStatus::STAND_BY
        : \App\Enums\EventGuestStatus::GOING;
@endphp
<div class="roster-row guest-status-{{ $event_guest->status->value }}"
     id="roster-guest-{{ $event_guest->id }}"
     @if($can_edit && !$is_cancelled)
         x-data="Events.rosterRow('guest-{{ $event_guest->id }}', false)"
     @endif>
    <div class="roster-row__main">
        <span class="roster-row__number"></span>
        <div class="roster-row__who">
            {{ $event_guest->name }}
            @if($event_guest->added_by_trooper_id > 0 && $event_guest->added_by_trooper)
                <div class="text-muted small fst-italic">
                    {{ __('events.Added by :name', ['name' => $event_guest->added_by_trooper->display_name]) }}
                </div>
            @endif
        </div>
        <div class="roster-row__costume"></div>
        <div class="roster-row__status">
            @if(
                    $viewer->can('update', $event)
                    && $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_shift->is_open
                    && in_array($event_guest->status, [\App\Enums\EventGuestStatus::STAND_BY, \App\Enums\EventGuestStatus::GOING], true)
                )
                <div class="d-flex gap-1 justify-content-end">
                    <form hx-post="{{ $guest_update_url }}"
                          hx-indicator="#transmission-bar-shift-{{ $guest_shift_id }}"
                          hx-select="#shift-container-{{ $guest_shift_id }}"
                          hx-target="#shift-container-{{ $guest_shift_id }}"
                          hx-swap="outerHTML">
                        @csrf
                        @if($event_guest->status === \App\Enums\EventGuestStatus::STAND_BY)
                            <input type="hidden" name="status" value="{{ \App\Enums\EventGuestStatus::GOING->value }}" />
                            <button type="submit" class="btn btn-sm btn-outline-success">{{ __('events.Approve') }}</button>
                        @else
                            <input type="hidden" name="status" value="{{ \App\Enums\EventGuestStatus::STAND_BY->value }}" />
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('events.Reject') }}</button>
                        @endif
                    </form>
                </div>
            @elseif($can_edit && $is_cancelled)
                <form hx-post="{{ $guest_update_url }}"
                      hx-indicator="#transmission-bar-shift-{{ $guest_shift_id }}"
                      hx-select="#shift-container-{{ $guest_shift_id }}"
                      hx-target="#shift-container-{{ $guest_shift_id }}"
                      hx-swap="outerHTML">
                    @csrf
                    <input type="hidden" name="status" value="{{ $again_status->value }}" />
                    <x-sign-up-status :status="$event_guest->status" :block="true" />
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-secondary-action">{{ __('events.Sign up again') }}</button>
                </form>
            @else
                <x-sign-up-status :status="$event_guest->status" />
            @endif

            @if(
                    $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_guest->status === \App\Enums\EventGuestStatus::GOING
                    && $event_guest->updated_by !== null
                )
                <div class="small text-muted fst-italic">
                    @if($event_guest->updated_at)
                        {{ __('events.Approved by :name on :date', ['name' => $event_guest->updated_by->display_name, 'date' => $event_guest->updated_at->localFormat('M j, Y g:ia')]) }}
                    @else
                        {{ __('events.Approved by :name', ['name' => $event_guest->updated_by->display_name]) }}
                    @endif
                </div>
            @endif
        </div>
        <div class="roster-row__edit">
            @if($can_edit && !$is_cancelled)
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        aria-controls="roster-guest-editor-{{ $event_guest->id }}"
                        x-bind:aria-expanded="editing"
                        x-on:click="editing = !editing">
                    <span x-show="!editing">{{ __('events.Edit') }}</span>
                    <span x-show="editing"
                          x-cloak>{{ __('events.Done') }}</span>
                </button>
            @endif
        </div>
    </div>
    @if($can_edit && !$is_cancelled)
        {{-- no id on the element x-show hides (see trooper.blade.php) --}}
        <div class="roster-row__editor text-md-end"
             x-show="editing"
             x-cloak>
            <form id="roster-guest-editor-{{ $event_guest->id }}"
                  hx-post="{{ $guest_update_url }}"
                  hx-confirm="{{ __('events.Cancel :name\'s sign-up for this shift?', ['name' => $event_guest->name]) }}"
                  hx-indicator="#transmission-bar-shift-{{ $guest_shift_id }}"
                  hx-select="#shift-container-{{ $guest_shift_id }}"
                  hx-target="#shift-container-{{ $guest_shift_id }}"
                  hx-swap="outerHTML">
                @csrf
                <input type="hidden" name="status" value="{{ \App\Enums\EventGuestStatus::CANCELLED->value }}" />
                <button type="submit" class="btn btn-link btn-sm p-0 text-danger">{{ __('events.Cancel their sign-up') }}</button>
            </form>
        </div>
    @endif
</div>
