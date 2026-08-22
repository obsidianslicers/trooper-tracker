<script lang="ts">
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
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
        <button
            type="button"
            class="btn btn-outline-danger"
            onclick={() => (vm.show_modal = true)}
        >
            Delete Account
        </button>
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
    <div class="mt-3">
        <label class="form-label" for="delete-confirm-input">
            Type <strong>DELETE</strong> to confirm:
        </label>
        <input
            id="delete-confirm-input"
            type="text"
            class="form-control"
            autocomplete="off"
            data-model="input"
        />
    </div>
</Modal>
