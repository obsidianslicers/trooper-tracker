<script lang="ts">
    import type { MissingCreditRow as Row, MissingCreditsViewModel } from "../models";

    interface Props {
        vm: MissingCreditsViewModel;
        row: Row;
    }
    let { vm, row }: Props = $props();

    let options = $derived(vm.optionsFor(row));
    let is_override = $derived(vm.isManualOverride(row));
</script>

<tr>
    <td>
        <a href={vm.trooperServiceRecordRoute(row.trooper_id)}>{row.trooper_name}</a>
    </td>
    <td>
        {row.event_name}
        {#if row.has_orphaned_db_value}
            <i
                class="fa fa-fw fa-circle-info text-muted ms-1"
                title="This shift already has a stored credit value that isn't resolving to a visible club — likely a membership sync mismatch."
            ></i>
        {/if}
    </td>
    <td>{row.shift_label}</td>
    <td>{row.costume_name ?? "N/A"}</td>
    <td>
        {#if options.length === 0}
            <span class="text-muted small">No eligible club found &mdash; contact a system administrator.</span>
        {:else}
            {#if is_override}
                <span class="badge bg-warning text-dark mb-1 d-block">Manual override (no club eligible)</span>
            {/if}
            {#each options as option (option.id)}
                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id={`org-${row.event_trooper_id}-${option.id}`}
                        checked={vm.isSelected(row, option.id)}
                        onchange={(e) => vm.toggleOrg(row, option.id, e.currentTarget.checked)}
                    />
                    <label class="form-check-label small" for={`org-${row.event_trooper_id}-${option.id}`}>
                        {option.name}
                    </label>
                </div>
            {/each}
        {/if}
    </td>
    <td class="text-end">
        {#if options.length > 0}
            <button
                type="button"
                class="btn btn-sm btn-warning"
                disabled={vm.assigning_id === row.event_trooper_id}
                onclick={() => vm.assign(row)}
            >
                {vm.assigning_id === row.event_trooper_id ? "Assigning…" : "Assign Credit"}
            </button>
        {/if}
    </td>
</tr>
