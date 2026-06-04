<div class="row mb-3 guest-status-{{ $event_guest->status->value }}">
    <div class="col-7 col-md-4 order-1 order-md-1">
        <i class="d-none fa fa-fw fa-times pe-2 text-danger cancelled-icon"></i>
        {{ $event_guest->name }}
        @if($event_guest->added_by_trooper_id > 0)
            <br />
            <i class="small text-muted">
                <i class="fa fa-fw fa-user-plus"></i>
                {{ $event_guest->added_by_trooper->display_name }}
            </i>
        @endif
    </div>
    <div class="col-12 col-md-5 order-3 order-md-2">
    </div>
    <div class="col-5 col-md-3 order-2 order-md-3 text-end">
        <div class="ps-3 ps-md-0">
            @if(
                    Auth::user()->can('update', $event)
                    && $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_shift->is_open
                    && in_array($event_guest->status, [\App\Enums\EventGuestStatus::STAND_BY, \App\Enums\EventGuestStatus::GOING], true)
                )
                <div class="d-flex gap-1 justify-content-end">
                    @if($event_guest->status === \App\Enums\EventGuestStatus::STAND_BY)
                        <form hx-post="{{ route('events.guest-update-htmx', compact('event_guest')) }}"
                              hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                              hx-select="#shift-container-{{ $event_guest->event_shift->id }}"
                              hx-target="#shift-container-{{ $event_guest->event_shift->id }}"
                              hx-swap="outerHTML">
                            @csrf
                            <input type="hidden"
                                   name="status"
                                   value="{{ \App\Enums\EventGuestStatus::GOING->value }}" />
                            <button type="submit"
                                    class="btn btn-sm btn-success">
                                <i class="fa fa-fw fa-check me-1"></i>
                                Approve
                            </button>
                        </form>
                    @endif
                    @if($event_guest->status === \App\Enums\EventGuestStatus::GOING)
                        <form hx-post="{{ route('events.guest-update-htmx', compact('event_guest')) }}"
                              hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                              hx-select="#shift-container-{{ $event_guest->event_shift->id }}"
                              hx-target="#shift-container-{{ $event_guest->event_shift->id }}"
                              hx-swap="outerHTML">
                            @csrf
                            <input type="hidden"
                                   name="status"
                                   value="{{ \App\Enums\EventGuestStatus::STAND_BY->value }}" />
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                <i class="fa fa-fw fa-ban me-1"></i>
                                Reject
                            </button>
                        </form>
                    @endif
                </div>
            @elseif($event_shift->is_open && $event_guest->canUpdateStatus(Auth::user()))
                <x-input-select :property="'status'"
                                :options="\App\Enums\EventGuestStatus::toSelectArray()"
                                :value="$event_guest->status->value"
                                hx-post="{{ route('events.guest-update-htmx', compact('event_guest')) }}"
                                hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                                hx-select="#shift-container-{{ $event_guest->event_shift->id }}"
                                hx-target="#shift-container-{{ $event_guest->event_shift->id }}"
                                hx-swap="outerHTML"
                                class="form-select-sm" />
            @else
                <span class="{{ $event_guest->status->color() }}">
                    {{ to_title($event_guest->status->name) }}
                    {!! $event_guest->status->iconTag() !!}
                </span>
            @endif

            @if(
                    $event->status === \App\Enums\EventStatus::MANUAL_SELECTION
                    && $event_guest->status === \App\Enums\EventGuestStatus::GOING
                    && $event_guest->updated_by !== null
                )
                <br />
                <i class="small text-muted">
                    Approved by {{ $event_guest->updated_by->display_name }}
                    @if($event_guest->updated_at)
                        on {{ $event_guest->updated_at->format('M j, Y g:ia') }}
                    @endif
                </i>
            @endif
        </div>
    </div>
</div>