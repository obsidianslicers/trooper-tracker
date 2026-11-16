<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { RequestDeletionViewModel } from "../models";

    let vm = new RequestDeletionViewModel();
</script>

<SlimView>
    <h5 class="text-danger mb-3">{t("Danger Zone")}</h5>

    <p class="text-muted mb-3">
        {t("Permanently delete your account and remove your personal information. Event participation history is retained in anonymized form. This cannot be undone after the 30-day grace period.")}
    </p>

    <SubmitButtonContainer>
        <DeleteButton
            outline={true}
            click={() => (vm.show_modal = true)}
            label={t("Delete Account")}
        />
    </SubmitButtonContainer>
</SlimView>

<Modal
    bind:show={vm.show_modal}
    title={t("Delete Account")}
    canClose={() => (vm.show_modal = false)}
>
    <p>
        {t("This will schedule your account for permanent deletion in")}
        <b>{t("30 days")}</b>. {t("You may cancel during this window by logging back in.")}
    </p>
    <ul>
        <li>{t("Your name, email, phone, and login credentials will be removed.")}</li>
        <li>{t("Event participation records are kept in anonymized form.")}</li>
        <li>{t("This action is irreversible after 30 days.")}</li>
    </ul>
    <form onsubmit={vm.delete}>
        <InputContainer>
            <InputText label={t("Type DELETE to confirm")} bind:value={vm.confirm} />
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton
                label={t("Delete")}
                danger={true}
                disabled={vm.confirm !== "DELETE"}
                submitting={vm.submitting}
            />
            <CancelButton click={() => (vm.show_modal = false)} />
        </SubmitButtonContainer>
    </form>
</Modal>
