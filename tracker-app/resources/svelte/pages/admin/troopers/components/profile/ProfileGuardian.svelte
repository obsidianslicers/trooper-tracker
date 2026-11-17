<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { untrack } from "svelte";
    import { ProfileGuardianViewModel, type ProfileGuardianPageData } from "../../models";

    interface Props {
        trooper_id: number;
        guardian: ProfileGuardianPageData;
    }
    let { trooper_id, guardian }: Props = $props();

    let vm = new ProfileGuardianViewModel(
        untrack(() => trooper_id),
        untrack(() => guardian),
    );
</script>

<SlimView>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputDate
                label={t("Date of Birth")}
                bind:value={vm.form.date_of_birth}
                errors={vm.errors.date_of_birth}
            />
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Guardian Email")}
                bind:value={vm.form.guardian_email}
                errors={vm.errors.guardian_email}
            />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton label={t("Update Guardian")} submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>
</SlimView>
