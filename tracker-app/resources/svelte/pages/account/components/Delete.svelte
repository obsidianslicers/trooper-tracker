<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { DeleteViewModel } from "../models";

    let vm = new DeleteViewModel();
</script>

<SlimView>
    <h5 class="text-danger mb-3">Danger Zone</h5>

    <p class="text-muted mb-3">
        Permanently delete your account and remove your personal information.
        Event participation history is retained in anonymized form. This cannot
        be undone after the 30-day grace period.
    </p>

    <SubmitButtonContainer>
        <DeleteButton
            outline={true}
            click={() => (vm.show_modal = true)}
            label="Delete Account"
        />
    </SubmitButtonContainer>
</SlimView>

<Modal
    bind:show={vm.show_modal}
    title="Delete Account"
    canClose={() => (vm.show_modal = false)}
>
    <p>
        This will schedule your account for permanent deletion in
        <b>30 days</b>. You may cancel during this window by logging back in.
    </p>
    <ul>
        <li>Your name, email, phone, and login credentials will be removed.</li>
        <li>Event participation records are kept in anonymized form.</li>
        <li>This action is irreversible after 30 days.</li>
    </ul>
    <InputContainer>
        <InputText label="Type DELETE to confirm" bind:value={vm.confirm} />
    </InputContainer>
    <SubmitButtonContainer>
        <DeleteButton
            click={() => vm.delete()}
            outline={false}
            disabled={vm.confirm !== "DELETE"}
            submitting={vm.submitting}
        />
        <CancelButton click={() => (vm.show_modal = false)} />
    </SubmitButtonContainer>
</Modal>
