<script lang="ts">
    import type { MissingCreditRow as Row, MissingCreditsViewModel } from "../models";

    interface Props {
        vm: MissingCreditsViewModel;
        row: Row;
    }
    let { vm, row }: Props = $props();

    let options = $derived(vm.optionsFor(row));
    let isFallback = $derived(vm.isFallback(row));

    const ORPHANED_CREDIT_TOOLTIP =
        "This shift already has a stored credit value that isn't resolving to a visible " +
        "club — likely a membership sync mismatch.";
</script>

<tr>
    <td>
        <a href={vm.trooperServiceRecordRoute(row.trooper_id)}>{row.trooper_name}</a>
    </td>
    <td>
        {row.event_name}
        {#if row.has_orphaned_db_value}
            <i class="fa fa-fw fa-circle-info text-muted ms-1" title={ORPHANED_CREDIT_TOOLTIP}></i>
        {/if}
    </td>
    <td>{row.shift_label}</td>
    <td>{row.costume_name ?? "N/A"}</td>
    <td>
        {#if row.trooper_has_no_club_membership}
            <span class="text-muted small">
                Not a member of any club — add them to a club first.
            </span>
        {:else if options.length === 0}
            <span class="text-muted small">
                No eligible club found &mdash; contact a system administrator.
            </span>
        {:else}
            {#if isFallback}
                <span class="badge bg-warning text-dark mb-1 d-block">
                    No club matched automatically — showing {row.trooper_name}'s current club
                    membership(s) instead.
                </span>
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
                    <label
                        class="form-check-label small"
                        for={`org-${row.event_trooper_id}-${option.id}`}
                    >
                        {option.name}
                    </label>
                </div>
            {/each}
        {/if}
    </td>
    <td class="text-end">
        {#if !row.trooper_has_no_club_membership && options.length > 0}
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
