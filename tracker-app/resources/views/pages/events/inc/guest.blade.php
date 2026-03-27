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
            @if($event_shift->is_open && $event_guest->canUpdateStatus($event_shift, Auth::user()))
                <x-input-select :property="'status'"
                                :options="\App\Enums\EventGuestStatus::toArray()"
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
        </div>
    </div>
</div>