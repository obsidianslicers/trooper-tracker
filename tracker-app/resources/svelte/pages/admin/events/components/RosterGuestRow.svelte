<script lang="ts">
    import { t } from "$lib/i18n";
    import SaveStatus from "$lib/components/form/SaveStatus.svelte";
    import { untrack } from "svelte";
    import { RosterGuestViewModel, type EventRoster, type RosterGuest } from "../models";

    interface Props {
        event_id: number;
        roster: EventRoster;
        guest: RosterGuest;
    }
    let { event_id, roster, guest }: Props = $props();

    const vm = new RosterGuestViewModel(
        untrack(() => event_id),
        untrack(() => guest),
    );
</script>

<tr>
    <td class="ps-4">
        {guest.name}
        {#if guest.added_by}
            <br />
            <i class="small text-muted">{t("Added by :name", { name: guest.added_by })}</i>
        {/if}
    </td>
    <td><i class="small text-muted">{t("Guest")}</i></td>
    <td>
        <select
            class="form-select form-select-sm"
            aria-label={t("Status")}
            name="guests[{guest.id}][status]"
            disabled={!roster.can_manage}
            bind:value={vm.form.status}
            onchange={vm.save}
        >
            {#each roster.guest_status_options as option (option.value)}
                <option value={option.value}>{option.label}</option>
            {/each}
        </select>
    </td>
    {#if roster.has_station_shifts}
        <td></td>
    {/if}
    <td></td>
    {#if roster.is_manual_selection}
        <td>{guest.approved_by ?? "-"}</td>
        <td>{guest.approved_at ?? "-"}</td>
    {/if}
    <td class="text-end"><SaveStatus state={vm.save_state} /></td>
</tr>
