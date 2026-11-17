<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import { PendingPermissionRequestViewModel } from "../../models/vms";

    interface Props {
        vm: PendingPermissionRequestViewModel;
    }

    let { vm }: Props = $props();
</script>

<div class="card-footer">
    {#if vm.error}
        <Alert type="danger">{vm.error}</Alert>
    {/if}
    {#if vm.denying}
        <form onsubmit={vm.deny}>
            <InputContainer>
                <InputText
                    label={t("Denial Reason (optional)")}
                    multiline={true}
                    bind:value={vm.denial_reason}
                />
            </InputContainer>
            <SubmitButtonContainer>
                <SubmitButton
                    label={t("Confirm Denial")}
                    danger={true}
                    submitting={vm.submitting}
                />
                <CancelButton click={() => (vm.denying = false)} />
            </SubmitButtonContainer>
        </form>
    {:else}
        <div class="d-flex justify-content-between">
            <DeleteButton
                label={t("Deny")}
                outline={false}
                icon={null}
                disabled={vm.submitting}
                click={() => (vm.denying = true)}
            />
            <CreateButton
                label={t("Approve")}
                outline={false}
                icon={null}
                submitting={vm.submitting}
                click={() => vm.approve()}
            />
        </div>
    {/if}
</div>
