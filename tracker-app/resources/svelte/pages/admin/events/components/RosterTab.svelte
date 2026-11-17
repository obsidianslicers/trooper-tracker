<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import { createPartialReloadOptions, getRoute } from "$lib/utils";
    import { router } from "@inertiajs/svelte";
    import type { EventRoster, RosterTrooper } from "../models";
    import RosterAddTrooper from "./RosterAddTrooper.svelte";
    import RosterGuestRow from "./RosterGuestRow.svelte";
    import RosterTrooperRow from "./RosterTrooperRow.svelte";

    interface Props {
        event_id: number;
        roster: EventRoster;
    }
    let { event_id, roster }: Props = $props();

    let pending_removal: RosterTrooper | null = $state(null);

    const confirmRemoval = () => {
        const row = pending_removal;
        pending_removal = null;

        if (row !== null) {
            router.post(
                getRoute("admin.events.troopers.remove", { event: event_id, event_trooper: row.id }),
                {},
                createPartialReloadOptions(),
            );
        }
    };

    const colspan = $derived(
        (roster.is_manual_selection ? 7 : 5) + (roster.has_station_shifts ? 1 : 0),
    );
</script>

{#if roster.is_manual_selection}
    <Alert type="info">
        {t("Manual Selection Event: all signups begin as Stand By and require Command Staff approval to move to Going.")}
    </Alert>
{/if}

<p class="text-muted small">
    {roster.can_manage
        ? t("Changes save as you make them.")
        : t("Only Command Staff for the hosting organization can change the roster.")}
</p>

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>{t("Trooper Name")}</th>
                <th>{t("Costume")}</th>
                <th>{t("Status")}</th>
                {#if roster.has_station_shifts}
                    <th>{t("Station")}</th>
                {/if}
                <th>{t("Credited Org")}</th>
                {#if roster.is_manual_selection}
                    <th>{t("Approved By")}</th>
                    <th>{t("Approved At")}</th>
                {/if}
                <th></th>
            </tr>
        </thead>
        {#each roster.shifts as shift (shift.id)}
            <tbody>
                <tr class="table-active">
                    <td {colspan}>{shift.time_display}</td>
                </tr>
                {#each shift.troopers as row (row.id)}
                    <RosterTrooperRow
                        {event_id}
                        {roster}
                        {shift}
                        {row}
                        onremove={(trooper) => (pending_removal = trooper)}
                    />
                {:else}
                    <tr>
                        <td {colspan} class="text-muted ps-4">
                            {t("No troopers assigned to this shift.")}
                        </td>
                    </tr>
                {/each}
                {#if shift.guests.length > 0}
                    <tr>
                        <td {colspan} class="ps-4 text-muted small">{t("Guests")}</td>
                    </tr>
                {/if}
                {#each shift.guests as guest (guest.id)}
                    <RosterGuestRow {event_id} {roster} {guest} />
                {/each}
                {#if roster.can_manage}
                    <tr>
                        <td {colspan} class="ps-4 py-2">
                            <RosterAddTrooper {event_id} {shift} />
                        </td>
                    </tr>
                {/if}
            </tbody>
        {/each}
    </table>
</div>

<ConfirmationModal
    show={pending_removal !== null}
    title={t("Remove from roster")}
    message={t("Remove :name from the roster?", { name: pending_removal?.display_name ?? "" })}
    confirmLabel={t("Remove")}
    danger={true}
    onConfirm={confirmRemoval}
    onCancel={() => (pending_removal = null)}
/>
