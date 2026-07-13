<div class="border rounded p-2 mt-2">
    <div class="small fw-semibold mb-2">
        <i class="fa fa-fw fa-user me-1"></i>{{ $trooper->display_name }}
        &mdash; select costume:
    </div>
    <input type="hidden"
           id="admin-add-trooper-{{ $event_shift->id }}"
           value="{{ $trooper->id }}" />
    @if(count($costumes) > 1)
        <select id="admin-add-costume-{{ $event_shift->id }}"
                class="form-select form-select-sm mb-2">
            @foreach($costumes as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    @elseif(count($costumes) === 1)
        <input type="hidden"
               id="admin-add-costume-{{ $event_shift->id }}"
               value="{{ array_key_first($costumes) }}" />
        <p class="small text-muted mb-2">Costume: <strong>{{ array_values($costumes)[0] }}</strong></p>
    @else
        <input type="hidden"
               id="admin-add-costume-{{ $event_shift->id }}"
               value="" />
        <p class="small text-muted mb-2">No approved costumes found — will add without costume.</p>
    @endif
    @if($eligible_orgs->count() > 1)
        <select id="admin-add-org-{{ $event_shift->id }}"
                class="form-select form-select-sm mb-2">
            <option value="">-- Select Organization --</option>
            @foreach($eligible_orgs as $org)
                <option value="{{ $org->id }}">{{ $org->name }}</option>
            @endforeach
        </select>
    @elseif($eligible_orgs->count() === 1)
        <input type="hidden"
               id="admin-add-org-{{ $event_shift->id }}"
               value="{{ $eligible_orgs->first()->id }}" />
        <p class="small text-muted mb-2">Trooping as <strong>{{ $eligible_orgs->first()->name }}</strong></p>
    @else
        <input type="hidden"
               id="admin-add-org-{{ $event_shift->id }}"
               value="" />
    @endif
    <div class="d-flex gap-2">
        <button type="button"
                class="btn btn-sm btn-success"
                hx-post="{{ route('admin.events.troopers.add', compact('event', 'event_shift')) }}"
                hx-vals="js:{trooper_id: document.getElementById('admin-add-trooper-{{ $event_shift->id }}').value, costume_id: document.getElementById('admin-add-costume-{{ $event_shift->id }}').value, organization_id: document.getElementById('admin-add-org-{{ $event_shift->id }}').value}"
                hx-params="trooper_id,costume_id,organization_id"
                hx-trigger="click">
            <i class="fa fa-fw fa-check me-1"></i>Confirm
        </button>
        <button type="button"
                class="btn btn-sm btn-outline-secondary"
                onclick="document.getElementById('admin-add-step2-{{ $event_shift->id }}').innerHTML=''">
            Cancel
        </button>
    </div>
</div>
