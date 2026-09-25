<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import type { MissingCreditRow as Row, MissingCreditsViewModel } from "../models";

    interface Props {
        vm: MissingCreditsViewModel;
        row: Row;
    }
    let { vm, row }: Props = $props();

    let options = $derived(vm.optionsFor(row));
    let isFallback = $derived(vm.isFallback(row));
    let isAssigning = $derived(vm.assigning_id === row.event_trooper_id);

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
                <InputCheckbox
                    label={option.name}
                    checked={vm.isSelected(row, option.id)}
                    onchange={() => vm.toggleOrg(row, option.id)}
                />
            {/each}
        {/if}
    </td>
    <td class="text-end">
        {#if !row.trooper_has_no_club_membership && options.length > 0}
            <Button
                btnclass="btn-warning btn-sm"
                label={isAssigning ? "Assigning…" : "Assign Credit"}
                icon={isAssigning ? "fa-solid fa-spinner fa-spin" : null}
                disabled={isAssigning}
                click={() => vm.assign(row)}
            />
        {/if}
    </td>
</tr>
