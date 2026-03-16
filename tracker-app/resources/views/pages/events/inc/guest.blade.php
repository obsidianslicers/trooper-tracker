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
        {{--
        @if($event_guest->canUpdateCostume($event_shift, Auth::user()))
        <x-input-select :property="'costume_id'"
                        :options="$event_guest->costumes"
                        :value="$event_guest->costume_id"
                        :placeholder="'-- Select Costume --'"
                        hx-post="{{ route('events.signup-update-htmx', compact('event_guest')) }}"
                        hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                        hx-swap="none"
                        class="form-select-sm mt-2 mt-md-0" />
        <x-input-select :property="'backup_costume_id'"
                        :options="$event_guest->costumes"
                        :value="$event_guest->backup_costume_id"
                        :placeholder="'-- Select Backup Costume (optional) --'"
                        hx-post="{{ route('events.signup-update-htmx', compact('event_guest')) }}"
                        hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                        hx-swap="none"
                        class="form-select-sm mt-2 mt-md-0" />
        @else
        @if($event_guest->is_handler)
        Handler
        @else
        @if($event_guest->costume != null)
        <b>
            {{ $event_guest->costume->name }}
        </b>
        <br />
        <i class="small text-muted">
            {{ $event_guest->costume_organizations }}
        </i>
        @endif
        @if($event_guest->backup_costume != null)
        <br />
        <i class="small text-muted">
            <i class="fa fa-fw fa-box-archive"></i>
            {{ $event_guest->backup_costume->name }}
            <br />
            {{ $event_guest->backup_costume_organizations }}
        </i>
        @endif
        @endif
        @endif
        --}}
    </div>
    <div class="col-5 col-md-3 order-2 order-md-3 text-end">
        <div class="ps-3 ps-md-0">
            {{--
            @if($event_shift->is_open && $event_guest->canUpdateStatus($event_shift, Auth::user()))
            <x-input-select :property="'status'"
                            :options="\App\Enums\EventTrooperStatus::toSignUpArray($event->tentative_signups_allowed)"
                            :value="$event_guest->status->value"
                            hx-post="{{ route('events.signup-update-htmx', compact('event_guest')) }}"
                            hx-indicator="#transmission-bar-shift-{{ $event_guest->event_shift->id }}"
                            hx-swap="none"
                            class="form-select-sm" />
            @else
            <span class="{{ $event_guest->status->color() }}">
                {{ to_title($event_guest->status->name) }}
                {!! $event_guest->status->iconTag() !!}
            </span>
            @endif
            --}}
        </div>
    </div>
</div>