<script lang="ts">
    import { t } from "$lib/i18n";
    import type { Option } from "$lib/domains/types.svelte";
    import { draftKey, type EventShift } from "../models";
    import NewShiftRow from "./NewShiftRow.svelte";
    import ShiftRow from "./ShiftRow.svelte";

    interface Props {
        event_id: number;
        shifts: EventShift[];
        is_active: boolean;
        status_options: Option[];
    }
    let { event_id, shifts, is_active, status_options }: Props = $props();

    let drafts: { key: number; date: string }[] = $state([]);

    // a new shift starts on the date of the last one
    const last_date = $derived(
        drafts[drafts.length - 1]?.date || shifts[shifts.length - 1]?.date || "",
    );

    const addShift = () => {
        drafts = [...drafts, { key: draftKey(), date: last_date }];
    };

    const saved = (key: number) => {
        drafts = drafts.filter((draft) => draft.key !== key);
    };
</script>

<p class="text-muted small">
    {#if is_active}
        {t("Changes save as you make them.")}
    {:else}
        {t("Shifts can only be changed while the event is active.")}
    {/if}
</p>

<div class="table-responsive">
    <table class="table align-middle" id="shifts">
        <thead>
            <tr>
                <th></th>
                <th>{t("Date")}</th>
                <th>{t("Starts At")}</th>
                <th>{t("Ends At")}</th>
                <th>{t("Status")}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each shifts as shift (shift.id)}
                <ShiftRow {event_id} {shift} {is_active} {status_options} />
            {/each}
            {#each drafts as draft (draft.key)}
                <NewShiftRow {event_id} {draft} onsaved={() => saved(draft.key)} />
            {/each}
        </tbody>
        {#if is_active}
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick={addShift}>
                            <i class="fa fa-fw fa-plus"></i>
                            {t("Add Shift")}
                        </button>
                    </td>
                </tr>
            </tfoot>
        {/if}
    </table>
</div>
