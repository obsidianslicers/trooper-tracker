<script lang="ts">
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { Option } from "$lib/domains/types.svelte";
    import { untrack } from "svelte";
    import {
        ProfileMembershipViewModel,
        type ProfileMembershipOrganization,
    } from "../../models";

    interface Props {
        trooper_id: number;
        organization: ProfileMembershipOrganization;
        status_options: Option[];
    }
    let { trooper_id, organization, status_options }: Props = $props();

    let vm = new ProfileMembershipViewModel(
        untrack(() => trooper_id),
        untrack(() => organization),
    );
</script>

<form onsubmit={vm.submit}>
    <div class="card mb-3">
        <div class="card-header">{organization.name}</div>
        <div class="card-body">
            {#if organization.can_edit}
                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <InputText
                            label="Identifier"
                            bind:value={vm.form.identifier}
                            errors={vm.errors.identifier}
                        />
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <InputSelect
                            label="Member Of"
                            options={organization.assignment_options}
                            bind:value={vm.form.assignment}
                            errors={vm.errors.assignment}
                        />
                    </div>
                </div>
                <InputContainer>
                    <InputSelect
                        label="Status"
                        options={status_options}
                        bind:value={vm.form.membership_status}
                        errors={vm.errors.membership_status}
                    />
                </InputContainer>
                <SubmitButtonContainer>
                    <SubmitButton label="Update" submitting={vm.submitting} />
                    {#if organization.is_member}
                        <Button
                            label="Remove Membership"
                            btnclass="btn-outline-danger"
                            click={vm.remove}
                        />
                    {/if}
                </SubmitButtonContainer>
            {:else}
                <p class="mb-0">
                    {organization.identifier ?? "No identifier"} &mdash;
                    {organization.membership_status}
                </p>
            {/if}
        </div>
    </div>
</form>

<ConfirmationModal
    show={vm.pending_removal}
    title="Remove membership"
    message={vm.confirm_message}
    confirmLabel="Remove"
    danger={true}
    onConfirm={vm.confirm}
    onCancel={vm.cancel}
/>
