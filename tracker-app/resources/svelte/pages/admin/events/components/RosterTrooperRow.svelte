<script lang="ts">
    import { t } from "$lib/i18n";
    import SaveStatus from "$lib/components/form/SaveStatus.svelte";
    import { untrack } from "svelte";
    import {
        RosterTrooperViewModel,
        type EventRoster,
        type RosterShift,
        type RosterTrooper,
    } from "../models";

    interface Props {
        event_id: number;
        roster: EventRoster;
        shift: RosterShift;
        row: RosterTrooper;
        onremove: (row: RosterTrooper) => void;
    }
    let { event_id, roster, shift, row, onremove }: Props = $props();

    const vm = new RosterTrooperViewModel(
        untrack(() => event_id),
        untrack(() => row),
    );
    const name = (field: string) => `troopers[${row.id}][${field}]`;
</script>

<tr>
    <td class="ps-4">
        <a href={row.profile_url}>{row.display_name}</a>
        {#if row.legal_name}
            <br />
            <i class="small text-muted">{row.legal_name}</i>
        {/if}
    </td>
    <td>
        <select
            class="form-select form-select-sm"
            aria-label={t("Costume")}
            name={name("costume_id")}
            disabled={!roster.can_manage}
            bind:value={vm.form.costume_id}
            onchange={vm.changeCostume}
        >
            <option value={null}>{t("-- No Costume --")}</option>
            {#each row.costume_options as option (option.value)}
                <option value={option.value}>{option.label}</option>
            {/each}
        </select>
        {#if row.backup_costume}
            <i class="small text-muted d-block mt-1">
                <i class="fa fa-fw fa-box-archive"></i>
                {row.backup_costume}
            </i>
        {/if}
    </td>
    <td>
        <select
            class="form-select form-select-sm"
            aria-label={t("Status")}
            name={name("status")}
            disabled={!roster.can_manage}
            bind:value={vm.form.status}
            onchange={vm.save}
        >
            {#each roster.status_options as option (option.value)}
                <option value={option.value}>{option.label}</option>
            {/each}
        </select>
    </td>
    {#if roster.has_station_shifts}
        <td>
            {#if shift.uses_stations}
                <select
                    class="form-select form-select-sm"
                    aria-label={t("Station")}
                    name={name("event_shift_station_id")}
                    disabled={!roster.can_manage}
                    bind:value={vm.form.event_shift_station_id}
                    onchange={vm.save}
                >
                    <option value={null}>{t("-- Select Station --")}</option>
                    {#each shift.station_options as option (option.value)}
                        <option value={option.value}>{option.label}</option>
                    {/each}
                </select>
            {:else}
                <span class="text-muted">-</span>
            {/if}
        </td>
    {/if}
    <td>
        {#each vm.org_options as org (org.id)}
            <div class="form-check mb-0">
                <input
                    id="org-{row.id}-{org.id}"
                    type="checkbox"
                    class="form-check-input"
                    disabled={!roster.can_manage}
                    checked={vm.isCredited(org.id)}
                    onchange={() => vm.toggleCredit(org.id)}
                />
                <label class="form-check-label small" for="org-{row.id}-{org.id}">{org.name}</label>
            </div>
        {:else}
            <span class="text-muted small">{t("(Unattached)")}</span>
        {/each}
    </td>
    {#if roster.is_manual_selection}
        <td>{row.approved_by ?? "-"}</td>
        <td>{row.approved_at ?? "-"}</td>
    {/if}
    <td class="text-end text-nowrap">
        <SaveStatus state={vm.save_state} />
        {#if roster.can_manage}
            <button
                type="button"
                class="btn btn-sm btn-link text-danger p-1"
                title={t("Remove from roster")}
                aria-label={t("Remove :name from the roster", { name: row.display_name })}
                onclick={() => onremove(row)}
            >
                <i class="fa fa-fw fa-times"></i>
            </button>
        {/if}
    </td>
</tr>
