<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { t } from "$lib/i18n";
    import { untrack } from "svelte";
    import { OverviewViewModel, type EventOverview } from "../models";
    import EventFields from "./EventFields.svelte";

    interface Props {
        event_id: number;
        overview: EventOverview;
    }
    let { event_id, overview }: Props = $props();

    let vm = new OverviewViewModel(
        untrack(() => event_id),
        untrack(() => overview),
    );

    // Empty ("") means whoever created the event runs it.
    let coordinator_options = $derived([
        { value: "", label: overview.coordinator_creator },
        ...overview.coordinator_options,
    ]);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            {#if overview.can_change_host}
                <InputSelect
                    label={t("Hosting Organization")}
                    options={overview.host_options}
                    bind:value={vm.form.organization_id}
                    errors={vm.errors.organization_id}
                />
            {:else}
                <span class="form-label">{t("Hosting Organization")}</span>
                <div class="form-control-plaintext">{overview.host_name}</div>
            {/if}
        </InputContainer>
        <EventFields
            {vm}
            event_types={overview.event_types}
            status_options={overview.status_options}
            smilies={overview.smilies}
            plugin_fields={overview.plugin_fields}
            {coordinator_options}
        />

        <SubmitButtonContainer>
            <SubmitButton label={t("Update")} submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>

    <TrooperStamps trooper_stamps={overview.trooper_stamps} />
</SlimView>
