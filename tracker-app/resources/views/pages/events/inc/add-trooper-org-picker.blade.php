<div class="border rounded p-2 mt-1">
    <div class="small fw-semibold mb-2">
        <i class="fa fa-fw fa-user me-1"></i>{{ $trooper->display_name }}
        @if($eligible_orgs->count() > 1)
            &mdash; select their organization:
        @elseif($eligible_orgs->count() === 1)
            &mdash; trooping as <strong>{{ $eligible_orgs->first()->name }}</strong>
        @endif
    </div>
    @if($eligible_orgs->count() > 1)
        <select id="add-org-confirm-{{ $event_shift->id }}"
                class="form-select form-select-sm mb-2">
            @foreach($eligible_orgs as $org)
                <option value="{{ $org->id }}">{{ $org->name }}</option>
            @endforeach
        </select>
    @elseif($eligible_orgs->count() === 1)
        <input type="hidden"
               id="add-org-confirm-{{ $event_shift->id }}"
               value="{{ $eligible_orgs->first()->id }}" />
    @endif
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-success"
                hx-post="{{ route('events.signup-htmx', compact('event_shift')) }}"
                hx-vals="js:{trooper_id: '{{ $trooper->id }}', organization_id: (document.getElementById('add-org-confirm-{{ $event_shift->id }}')?.value || ''), is_handler: {{ $is_handler ? 1 : 0 }}, event_shift_station_id: (document.getElementById('station-picker-{{ $event_shift->id }}')?.value || '')}"
                hx-select="#shift-container-{{ $event_shift->id }}"
                hx-target="#shift-container-{{ $event_shift->id }}"
                hx-swap="outerHTML"
                hx-indicator="#transmission-bar-shift-{{ $event_shift->id }}">
            <i class="fa fa-fw fa-check me-1"></i>Confirm
        </button>
        <button class="btn btn-sm btn-outline-secondary"
                type="button"
                onclick="document.getElementById('add-trooper-step2-{{ $event_shift->id }}').innerHTML=''">
            Cancel
        </button>
    </div>
</div>
