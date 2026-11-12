<script lang="ts">
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";

    interface Props {
        show: boolean;
        title?: string;
        message?: string;
        confirmLabel?: string;
        danger?: boolean;
        submitting?: boolean;
        onConfirm: () => void;
        onCancel: () => void;
    }

    let {
        show,
        title = "Please confirm",
        message = "Are you sure you want to continue?",
        confirmLabel = "Confirm",
        danger = false,
        submitting = false,
        onConfirm,
        onCancel,
    }: Props = $props();

    const submit = (e: Event) => {
        e.preventDefault();
        onConfirm();
    };
</script>

<Modal bind:show {title} canClose={onCancel}>
    <p>{message}</p>
    <form onsubmit={submit}>
        <SubmitButtonContainer>
            <SubmitButton label={confirmLabel} {danger} {submitting} />
            <CancelButton click={onCancel} />
        </SubmitButtonContainer>
    </form>
</Modal>
