<script lang="ts">
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";

    interface Props {
        show: boolean;
        label: string | undefined;
        deleting: boolean;
        onDelete: (e: Event) => void;
        onCancel: () => void;
    }

    let { show, label, deleting, onDelete, onCancel }: Props = $props();
</script>

<Modal bind:show title="This cannot be undone" canClose={onCancel}>
    <p>Delete "{label}"?</p>
    <form onsubmit={onDelete}>
        <SubmitButtonContainer>
            <SubmitButton label="Delete" danger={true} submitting={deleting} />
            <CancelButton click={onCancel} />
        </SubmitButtonContainer>
    </form>
</Modal>
