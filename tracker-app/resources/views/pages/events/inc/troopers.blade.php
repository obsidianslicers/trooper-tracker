<div class="row mb-3">
    <div class="col-7 col-md-4 order-1 order-md-1">
        <div class="fw-bold">
            {{ $event_trooper->trooper->name }}
            @if($event_trooper->added_by_trooper_id > 0)
                <br />
                <i class="small text-muted">
                    <i class="fa fa-fw fa-user-plus"></i>
                    {{ $event_trooper->added_by_trooper->name }}
                </i>
            @endif
        </div>
    </div>
    <div class="col-12 col-md-5 order-3 order-md-2">
        @if($event_shift->is_open)
            @if(Auth::user()->id == $event_trooper->trooper_id)
                <x-input-select :property="'costume_id'"
                                :options="\App\Models\OrganizationCostume::forEvent($event, Auth::user())->toOptions('name', 'id')"
                                :value="$event_trooper->costume_id"
                                :placeholder="'-- Select Costume --'"
                                hx-post="{{ route('events.signup-update-htmx', compact( 'event_trooper')) }}"
                                hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                                hx-swap="none"
                                class="form-select-sm mt-2 mt-md-0" />
            @else
                @if($event_trooper->is_handler)
                    Handler
                @elseif($event_trooper->organization_costume != null)
                    {{ $event_trooper->organization_costume->name ?? 'N/A' }}
                    <br />
                    <i class="small text-muted">
                        {{ $event_trooper->organization_costume->organization->name }}
                    </i>
                @endif
            @endif
        @endif
    </div>
    <div class="col-5 col-md-3 order-2 order-md-3 text-end">
        <div class="ps-3 ps-md-0">
            @if($event->is_open)
                @if(Auth::user()->id == $event_trooper->trooper_id)
                    @if($event_trooper->canUpdateStatus($event_shift))
                        <x-input-select :property="'status'"
                                        :options="\App\Enums\EventTrooperStatus::toSignUpArray()"
                                        :value="$event_trooper->status->value"
                                        hx-post="{{ route('events.signup-update-htmx', compact( 'event_trooper')) }}"
                                        hx-indicator="#transmission-bar-shift-{{ $event_trooper->event_shift->id }}"
                                        hx-swap="none"  
                                        class="form-select-sm" />
                    @else
                        {{ to_title($event_trooper->status->name) }}
                    @endif
                @else
                    {{ to_title($event_trooper->status->name) }}
                @endif
            @endif
        </div>
    </div>
</div>