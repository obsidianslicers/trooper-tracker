<tr id="club-row-{{ $org->id }}">
    <td>
        {{ $org->parent?->name ? $org->parent->name . ' — ' : '' }}{{ $org->name }}
    </td>
    <td>
        @if(!empty($org->identifier_validation))
            <input type="text"
                   name="identifier"
                   class="form-control form-control-sm"
                   placeholder="e.g. TK0000"
                   style="max-width: 120px;" />
        @else
            <span class="text-muted">—</span>
        @endif
        <input type="hidden" name="organization_id" value="{{ $org->id }}" />
    </td>
    <td class="text-end">
        <button type="button"
                class="btn btn-sm btn-outline-primary"
                hx-post="{{ route('account.club-memberships-htmx') }}"
                hx-include="closest tr"
                hx-target="#club-row-{{ $org->id }}"
                hx-swap="outerHTML"
                hx-indicator="#transmission-bar-club-memberships">
            Request to Join
        </button>
    </td>
</tr>
