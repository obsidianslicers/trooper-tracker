<!--
    Adds a trooper to a shift: pick them, then their costume, club and station.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import { untrack } from "svelte";
    import { AddTrooperViewModel, type RosterShift } from "../models";

    interface Props {
        event_id: number;
        shift: RosterShift;
        /** False for an event type troopers don't wear a costume to: no costume to pick. */
        requires_costume?: boolean;
    }
    let { event_id, shift, requires_costume = true }: Props = $props();

    const vm = new AddTrooperViewModel(
        untrack(() => event_id),
        untrack(() => shift.id),
    );
</script>

<!-- The picker stays mounted so its dialog can finish closing. -->
<div class="col-12 col-md-6">
    <TrooperPicker
        bind:selected={vm.selected}
        label={t("Add Trooper")}
        placeholder={vm.loading ? t("Loading...") : t("Pick a trooper to add")}
        modal_title={t("Find a Trooper")}
        clearable={false}
        mode="admin"
        onSelected={vm.picked}
    />
</div>

{#if vm.choices}
    <div class="border rounded p-2 mt-2">
        <div class="small fw-semibold mb-2">
            <i class="fa fa-fw fa-user me-1"></i>
            {t("Add :name to this shift", { name: vm.choices.display_name })}
        </div>
        {#if requires_costume && vm.choices.costume_options.length > 0}
            <select class="form-select form-select-sm mb-2" aria-label={t("Costume")} bind:value={vm.costume_id}>
                {#each vm.choices.costume_options as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                {/each}
            </select>
        {:else if requires_costume}
            <p class="small text-muted mb-2">
                {t("No approved costumes found — will add without costume.")}
            </p>
        {/if}
        {#if vm.choices.organization_options.length > 1}
            <select class="form-select form-select-sm mb-2" aria-label={t("Organization")} bind:value={vm.organization_id}>
                <option value="">{t("-- Select Organization --")}</option>
                {#each vm.choices.organization_options as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                {/each}
            </select>
        {:else if vm.choices.organization_options.length === 1}
            <p class="small text-muted mb-2">
                {t("Trooping as :organization", { organization: vm.choices.organization_options[0].label })}
            </p>
        {/if}
        {#if shift.uses_stations}
            <select class="form-select form-select-sm mb-2" aria-label={t("Station")} bind:value={vm.event_shift_station_id}>
                <option value="">{t("-- Select Station --")}</option>
                {#each vm.choices.station_options as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                {/each}
            </select>
        {/if}
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-success" disabled={vm.submitting} onclick={vm.confirm}>
                <i class="fa fa-fw fa-check me-1"></i>{t("Add to Roster")}
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick={vm.cancel}>
                {t("Cancel")}
            </button>
        </div>
    </div>
{/if}
