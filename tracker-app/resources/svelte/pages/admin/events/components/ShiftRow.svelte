<script lang="ts">
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import SaveStatus from "$lib/components/form/SaveStatus.svelte";
    import type { Option } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";
    import Sortable from "sortablejs";
    import { untrack } from "svelte";
    import { ShiftViewModel, type EventShift } from "../models";
    import DateCell from "./DateCell.svelte";

    interface Props {
        event_id: number;
        shift: EventShift;
        is_active: boolean;
        status_options: Option[];
    }
    let { event_id, shift, is_active, status_options }: Props = $props();

    const vm = new ShiftViewModel(
        untrack(() => event_id),
        untrack(() => shift.id),
        untrack(() => shift),
    );
    const name = (field: string) => `shifts[${shift.id}][${field}]`;

    let show_stations = $state(untrack(() => shift.stations.length > 0));

    // Sortable moves the row itself; put it back and let Svelte move it from the data.
    const sortStations = (node: HTMLElement) => {
        const instance = Sortable.create(node, {
            handle: ".station-drag-handle",
            draggable: ".draggable",
            animation: 150,
            onEnd: ({ item, from, oldIndex, newIndex }) => {
                if (
                    oldIndex === undefined ||
                    newIndex === undefined ||
                    oldIndex === newIndex
                ) {
                    return;
                }

                from.removeChild(item);
                from.insertBefore(item, from.children[oldIndex] ?? null);
                vm.moveStation(oldIndex, newIndex);
            },
        });

        return () => instance.destroy();
    };

    // Removing the last station folds the editor back to the "+ Stations" link.
    $effect(() => {
        if (vm.form.stations.length === 0) {
            show_stations = false;
        }
    });

    const openStations = () => {
        show_stations = true;

        if (vm.form.stations.length === 0) {
            vm.addStation();
        }
    };
</script>

<tr>
    <td class="align-top">{shift.day}</td>
    <td class="align-top">
        <DateCell
            name={name("date")}
            label={t("Date")}
            disabled={!is_active}
            bind:value={vm.form.date}
            invalid={!!vm.error("date")}
            onchange={vm.change}
        />
        <InputError errors={vm.error("date")} />
    </td>
    <td class="align-top">
        <input
            type="time"
            class={[
                "form-control form-control-sm",
                vm.error("starts_at") ? "is-invalid" : "",
            ]}
            aria-label={t("Starts At")}
            name={name("starts_at")}
            disabled={!is_active}
            bind:value={vm.form.starts_at}
            onchange={vm.change}
        />
        <InputError errors={vm.error("starts_at")} />
    </td>
    <td class="align-top">
        <input
            type="time"
            class={[
                "form-control form-control-sm",
                vm.error("ends_at") ? "is-invalid" : "",
            ]}
            aria-label={t("Ends At")}
            name={name("ends_at")}
            disabled={!is_active}
            bind:value={vm.form.ends_at}
            onchange={vm.change}
        />
        <InputError errors={vm.error("ends_at")} />
    </td>
    <td class="align-top">
        <select
            class={[
                "form-select form-select-sm",
                vm.error("status") ? "is-invalid" : "",
            ]}
            aria-label={t("Status")}
            name={name("status")}
            disabled={!is_active}
            bind:value={vm.form.status}
            onchange={vm.change}
        >
            {#each status_options as option (option.value)}
                <option value={option.value}>{option.label}</option>
            {/each}
        </select>
        <InputError errors={vm.error("status")} />
        {#if is_active && !show_stations}
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary btn-secondary-action mt-1"
                onclick={openStations}
            >
                <i class="fa fa-fw fa-plus me-1"></i>
                {t("Stations")}
            </button>
        {/if}
    </td>
    <td class="align-top">
        <SaveStatus state={vm.save_state} />
        <ConfirmationModal
            show={vm.pending_removal !== null}
            title={t("Remove station")}
            message={vm.confirm_message}
            confirmLabel={t("Remove")}
            danger={true}
            onConfirm={vm.confirmRemoval}
            onCancel={vm.cancelRemoval}
        />
    </td>
</tr>
{#if show_stations}
    <tr>
        <td colspan="6" class="pt-2 pb-3 border-top-0">
            <div class="station-editor ms-md-5">
                <div class="station-editor__header">
                    <div class="d-flex align-items-start gap-2">
                        <span class="station-editor__icon" aria-hidden="true">
                            <i class="fa fa-fw fa-map-marker"></i>
                        </span>
                        <div>
                            <div class="fw-semibold">{t("Stations")}</div>
                            <div class="text-muted small mt-1">
                                <i class="fa fa-fw fa-grip-vertical me-1"></i>
                                {t(
                                    "Drag stations to change their display order.",
                                )}
                            </div>
                        </div>
                    </div>
                    {#if is_active}
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success flex-shrink-0"
                            onclick={vm.addStation}
                        >
                            <i class="fa fa-fw fa-plus me-1"></i>
                            {t("Add Station")}
                        </button>
                    {/if}
                </div>
                <div class="station-list" {@attach sortStations}>
                    {#each vm.form.stations as station (station.key)}
                        <div
                            class={[
                                "station-item",
                                station.key > 0 ? "draggable" : "",
                            ]}
                        >
                            {#if station.key > 0}
                                <button
                                    type="button"
                                    class="station-drag-handle"
                                    title={t("Drag to reorder")}
                                    aria-label={t("Drag to reorder")}
                                >
                                    <i class="fa fa-fw fa-grip-vertical"></i>
                                </button>
                            {:else}
                                <span class="station-drag-placeholder"></span>
                            {/if}
                            <div class="station-field station-field--name">
                                <label
                                    class="form-label small mb-1"
                                    for="station-{shift.id}-{station.key}-name"
                                    >{t("Station name")}</label
                                >
                                <input
                                    id="station-{shift.id}-{station.key}-name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    name={name(
                                        `stations][${station.key}][name`,
                                    )}
                                    disabled={!is_active}
                                    bind:value={station.name}
                                    onchange={() => vm.changeStation(station)}
                                />
                            </div>
                            <div class="station-field station-field--capacity">
                                <label
                                    class="form-label small mb-1"
                                    for="station-{shift.id}-{station.key}-capacity"
                                    >{t("Capacity")}</label
                                >
                                <input
                                    id="station-{shift.id}-{station.key}-capacity"
                                    type="number"
                                    min="1"
                                    max="999"
                                    step="1"
                                    class="form-control form-control-sm"
                                    name={name(
                                        `stations][${station.key}][troopers_allowed`,
                                    )}
                                    disabled={!is_active}
                                    bind:value={station.troopers_allowed}
                                    onchange={() => vm.changeStation(station)}
                                />
                            </div>
                            <div class="station-item__action">
                                {#if is_active}
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title={t("Remove station")}
                                        onclick={() =>
                                            vm.removeStation(station)}
                                    >
                                        <i class="fa fa-fw fa-trash"></i>
                                        <span class="visually-hidden"
                                            >{t("Remove :station", {
                                                station: station.name,
                                            })}</span
                                        >
                                    </button>
                                {/if}
                            </div>
                            <InputError errors={vm.stationError(station)} />
                        </div>
                    {/each}
                </div>
            </div>
        </td>
    </tr>
{/if}
