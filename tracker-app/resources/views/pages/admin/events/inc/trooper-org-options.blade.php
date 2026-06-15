@forelse($org_options as $org)
    <div class="form-check mb-0">
        <input type="checkbox"
               @if(!$disabled) name="troopers[{{ $event_trooper->id }}][organization_ids][]" @endif
               value="{{ $org->id }}"
               id="org_{{ $event_trooper->id }}_{{ $org->id }}"
               class="form-check-input"
               @checked(in_array($org->id, $credited_ids))
               @disabled($disabled)>
        <label class="form-check-label small"
               for="org_{{ $event_trooper->id }}_{{ $org->id }}">
            {{ $org->name }}
        </label>
    </div>
@empty
    <span class="text-muted small">(Unattached)</span>
@endforelse
