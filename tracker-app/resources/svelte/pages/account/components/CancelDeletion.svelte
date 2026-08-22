<script lang="ts">
    import Alert from "$lib/components/ui/Alert.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import { getRoute } from "$lib/utils";
    import { router } from "@inertiajs/svelte";

    interface Props {
        deletionDate: string | null;
    }

    let { deletionDate }: Props = $props();

    let submitting = $state(false);

    function cancel() {
        if (submitting) return;
        submitting = true;
        router.delete(getRoute("account.delete.cancel"), {
            onFinish: () => {
                submitting = false;
            },
        });
    }
</script>

<Alert type="danger" classes="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div>
        <strong>Account deletion scheduled.</strong>
        Your account will be permanently deleted on
        <strong>{deletionDate}</strong>.
    </div>
    <SubmitButton label="Cancel Deletion" {submitting} click={cancel} />
</Alert>
