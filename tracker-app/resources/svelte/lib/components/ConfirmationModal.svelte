<script lang="ts">
    import { t } from "$lib/i18n";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import type { Snippet } from "svelte";

    interface Props {
        show: boolean;
        title?: string;
        message?: string;
        confirmLabel?: string;
        danger?: boolean;
        submitting?: boolean;
        onConfirm: () => void;
        onCancel: () => void;
        /** More detail shown under the message. */
        children?: Snippet;
    }

    let {
        show,
        title = t("Please confirm"),
        message = "Are you sure you want to continue?",
        confirmLabel = t("Confirm"),
        danger = false,
        submitting = false,
        onConfirm,
        onCancel,
        children,
    }: Props = $props();

    const submit = (e: Event) => {
        e.preventDefault();
        onConfirm();
    };
</script>

<Modal bind:show {title} canClose={onCancel}>
    <p>{message}</p>
    {@render children?.()}
    <form onsubmit={submit}>
        <SubmitButtonContainer>
            <SubmitButton label={confirmLabel} {danger} {submitting} />
            <CancelButton click={onCancel} />
        </SubmitButtonContainer>
    </form>
</Modal>
