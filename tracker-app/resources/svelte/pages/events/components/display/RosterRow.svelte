<!--
    One row of a shift's roster: who, their costume and their status. Rows the viewer may change
    (their own, friends they added, a child they're the guardian of) get Edit, which opens the
    pickers under the row so it keeps its spot in sign-up order.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { trooperRowKey, type EventDisplayViewModel, type TrooperRow } from "../../models";
    import RowEditor from "./RowEditor.svelte";
    import RowStatus from "./RowStatus.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        row: TrooperRow;
        shift_id: number;
        is_open: boolean;
    }

    let { vm, row, shift_id, is_open }: Props = $props();

    const key = $derived(trooperRowKey(row.id));
    const editing = $derived(row.editor !== null && vm.isEditing(key));
</script>

<div class={["roster-row", `trooper-status-${row.status.value}`, row.is_mine && "roster-row--mine"]}
     id="roster-row-{row.id}">
    <div class="roster-row__main">
        <span class="roster-row__number text-muted">{row.number ?? ""}</span>
        <div class="roster-row__who">
            <a href={row.trooper.url}>{row.trooper.name}</a>
            {#if row.is_mine}
                <span class="badge text-bg-secondary ms-1">{t("You")}</span>
            {/if}
            <div class="text-muted small">{row.trooper.legal_name}</div>
            {#if row.needs_verification}
                <span class="badge text-bg-warning">{t("Needs verification")}</span>
            {/if}
            {#if row.added_by}
                <div class="text-muted small fst-italic">{t("Added by :name", { name: row.added_by })}</div>
            {/if}
        </div>
        <div class="roster-row__costume">
            {#if row.station}
                <div class="small text-muted mb-1"><i class="fa fa-fw fa-location-dot me-1"></i>{row.station}</div>
            {/if}
            {#if row.costume}
                {#if row.costume.name}
                    <b>{row.costume.name}</b>
                    <br />
                    {#if row.costume.organizations}
                        <i class="small text-muted">{row.costume.organizations}</i>
                    {/if}
                {:else if row.costume.is_handler}
                    {t("Handler")}
                {:else if row.costume.is_without_costume}
                    <i class="small text-muted">{t("Attending without a costume")}</i>
                {:else}
                    <i class="small text-muted">{t("No costume selected")}</i>
                {/if}
                {#if row.costume.backup}
                    <div class="small text-muted">{t("Backup: :costume", { costume: row.costume.backup })}</div>
                {/if}
            {/if}
        </div>
        <div class="roster-row__status">
            <RowStatus {vm} {row} {shift_id} {is_open} />
        </div>
        <div class="roster-row__edit">
            {#if row.editor}
                <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action"
                        aria-controls="roster-row-editor-{row.id}" aria-expanded={editing}
                        onclick={() => vm.toggleEdit(key)}>
                    {editing ? t("Done") : t("Edit")}
                </button>
            {/if}
        </div>
    </div>
    {#if row.editor && editing}
        <div class="roster-row__editor">
            <RowEditor {vm} {row} editor={row.editor} {shift_id}
                       has_picker_beside={row.editor.costumes !== null || row.editor.stations !== null} />
        </div>
    {/if}
</div>
