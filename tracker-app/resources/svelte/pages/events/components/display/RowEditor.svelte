<!--
    A roster row's Edit area: station, club, costume and backup costume, each saved the moment
    it's picked; plans (Going / Tentative) where the event offers both; and Cancel, after a
    confirmation.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { untrack } from "svelte";
    import type { EventDisplayViewModel, RowEditor, TrooperRow } from "../../models";
    import BusySpinner from "./BusySpinner.svelte";

    interface Props {
        vm: EventDisplayViewModel;
        row: TrooperRow;
        editor: RowEditor;
        shift_id: number;
        has_picker_beside: boolean;
    }

    let { vm, row, editor, shift_id, has_picker_beside }: Props = $props();

    let wants_backup = $state(untrack(() => (editor.costumes?.backup_value ?? "") !== ""));

    const save = (field: string, value: string) =>
        vm.send(row.actions.update_url, { [field]: value }, { shift_id });

    const changed = (field: string) => (e: Event) => save(field, (e.currentTarget as HTMLSelectElement).value);

    const cancel = () => vm.confirm({
        title: row.is_mine ? t("Cancel my sign-up") : t("Cancel their sign-up"),
        message: editor.cancel_confirm,
        label: row.is_mine ? t("Cancel my sign-up") : t("Cancel their sign-up"),
        danger: true,
        run: () => vm.send(row.actions.update_url, { status: "cancelled" }, { shift_id, busy: cancel_key }),
    });

    const cancel_key = $derived(`cancel-${row.id}`);

    const id = (name: string) => `${name}-${row.id}`;
</script>

<div id="roster-row-editor-{row.id}">
    <div class="row g-3">
        <div class="col-12 col-md">
            {#if editor.stations}
                <label class="form-label small text-muted mb-1" for={id("station")}>{t("Station")}</label>
                <select id={id("station")} class="form-select form-select-sm mt-2 mt-md-0 mb-2"
                        value={editor.stations.value === null ? "" : String(editor.stations.value)}
                        onchange={changed("event_shift_station_id")}>
                    <option value="">{t("-- Select Station --")}</option>
                    {#each editor.stations.options as option (option.value)}
                        <option value={String(option.value)}>{option.label}</option>
                    {/each}
                </select>
            {/if}

            {#if editor.organizations && editor.organizations.options.length > 1}
                <label class="form-label small text-muted mb-1" for={id("organization")}>{t("Organization")}</label>
                <select id={id("organization")} class="form-select form-select-sm mt-2 mt-md-0"
                        value={editor.organizations.value === null ? "" : String(editor.organizations.value)}
                        onchange={changed("organization_id")}>
                    <option value="">{t("-- Select Organization --")}</option>
                    {#each editor.organizations.options as option (option.value)}
                        <option value={String(option.value)}>{option.label}</option>
                    {/each}
                </select>
            {:else if editor.organizations && editor.organizations.options.length === 1}
                <div class="small text-muted">
                    <i class="fa fa-fw fa-building me-1"></i>
                    {t("Trooping as :organization", { organization: editor.organizations.options[0].label })}
                </div>
            {/if}

            {#if editor.costumes}
                <label class={["form-label small text-muted mb-1", editor.organizations && "mt-2"]}
                       for={id("costume")}>{t("Costume")}</label>
                <select id={id("costume")} class="form-select form-select-sm mt-2 mt-md-0"
                        value={editor.costumes.value} onchange={changed("costume_id")}>
                    <option value="">{t("-- Select Costume --")}</option>
                    <option value="none">{t("Attending without a costume")}</option>
                    {#each editor.costumes.options as option (option.value)}
                        <option value={String(option.value)}>{option.label}</option>
                    {/each}
                </select>

                {#if wants_backup}
                    <label class="form-label small text-muted mb-1 mt-2" for={id("backup")}>
                        {t("Backup costume (optional)")}
                    </label>
                    <select id={id("backup")} class="form-select form-select-sm"
                            value={editor.costumes.backup_value} onchange={changed("backup_costume_id")}>
                        <option value="">{t("-- None --")}</option>
                        {#each editor.costumes.options as option (option.value)}
                            <option value={String(option.value)}>{option.label}</option>
                        {/each}
                    </select>
                {:else}
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-secondary-action mt-2"
                            onclick={() => (wants_backup = true)}>
                        {t("Add a backup costume")}
                    </button>
                {/if}
            {:else if !editor.requires_costume}
                <span class="small text-muted">
                    <i class="fa fa-fw fa-circle-info me-1" aria-hidden="true"></i>{t("No costume needed for this event")}
                </span>
            {/if}
        </div>
        <div class="col-12 col-md-auto align-self-start text-md-end roster-row__editor-actions">
            {#if has_picker_beside}
                <!-- the height of the picker's label, so plans and Cancel line up with the picker beside them -->
                <span class="form-label small mb-1 d-none d-md-block invisible" aria-hidden="true">&nbsp;</span>
            {/if}
            {#if editor.plans || editor.can_cancel}
                <div class="d-flex flex-wrap gap-3 align-items-baseline justify-content-md-end">
                    {#if editor.plans}
                        <select class="form-select form-select-sm w-auto" aria-label={t("Your plans")}
                                value={editor.plans.value} onchange={changed("status")}>
                            {#each editor.plans.options as option (option.value)}
                                <option value={String(option.value)}>{option.label}</option>
                            {/each}
                        </select>
                    {/if}
                    {#if editor.can_cancel}
                        <button type="button" class="btn btn-link btn-sm p-0 text-danger" onclick={cancel}
                                disabled={vm.sending} aria-busy={vm.isBusy(cancel_key)}>
                            <BusySpinner busy={vm.isBusy(cancel_key)} />
                            {row.is_mine ? t("Cancel my sign-up") : t("Cancel their sign-up")}
                        </button>
                    {/if}
                </div>
            {/if}
        </div>
    </div>
</div>
