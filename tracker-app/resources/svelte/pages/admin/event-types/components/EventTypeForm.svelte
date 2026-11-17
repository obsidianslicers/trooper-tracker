<script lang="ts">
    import { t } from "$lib/i18n";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { ISubmitableViewModel } from "$lib/domains/types.svelte";
    import { getRoute } from "$lib/utils";
    import type { Snippet } from "svelte";
    import type { IEventTypeForm } from "../models/types";

    interface Props {
        vm: ISubmitableViewModel<IEventTypeForm>;
        label: string;
        /** Shown under the "counts toward troop totals" checkbox. */
        counted_notice?: Snippet;
    }

    let { vm, label, counted_notice }: Props = $props();
</script>

<form onsubmit={vm.submit}>
    <InputContainer>
        <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
    </InputContainer>

    <InputContainer>
        <InputText
            label={t("Description")}
            multiline={true}
            bind:value={vm.form.description}
            errors={vm.errors.description}
        />
        <InputHelp>
            {t("Shown under the Event Type list on the event form, to help coordinators pick the right type.")}
        </InputHelp>
    </InputContainer>

    <InputContainer>
        <InputCheckbox
            label={t("Counts toward troop totals")}
            bind:checked={vm.form.is_counted}
            errors={vm.errors.is_counted}
        />
        <InputHelp>
            {t("Uncheck to make this a non-troop-credit type: its events don't add to troop totals, milestones, leaderboards or tour of duty. Members see a notice on them.")}
        </InputHelp>
        {@render counted_notice?.()}
    </InputContainer>

    <InputContainer>
        <InputCheckbox
            label={t("Offered on the event form")}
            bind:checked={vm.form.is_active}
            errors={vm.errors.is_active}
        />
        <InputHelp>
            {t("Uncheck to hide this type from new events. Events that already use it keep it.")}
        </InputHelp>
    </InputContainer>

    <SubmitButtonContainer>
        <SubmitButton {label} submitting={vm.submitting} disabled={!vm.dirty} />
        <CancelButton href={getRoute("admin.event-types.index")} />
    </SubmitButtonContainer>
</form>
