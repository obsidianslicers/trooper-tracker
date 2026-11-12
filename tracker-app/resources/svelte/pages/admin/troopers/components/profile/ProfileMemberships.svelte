<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { untrack } from "svelte";
    import {
        ProfileMembershipsViewModel,
        type ProfileMembershipsPageData,
    } from "../../models";

    interface Props {
        trooper_id: number;
        memberships: ProfileMembershipsPageData;
    }
    let { trooper_id, memberships }: Props = $props();

    let vm = new ProfileMembershipsViewModel(
        untrack(() => trooper_id),
        untrack(() => memberships),
    );
</script>

<SlimView>
    {#each memberships.organizations as organization (organization.id)}
        <form onsubmit={(e) => vm.submitOrganization(e, organization.id)}>
            <div class="card mb-3">
                <div class="card-header">{organization.name}</div>
                <div class="card-body">
                    {#if organization.can_edit && vm.form.organizations[organization.id]}
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <InputText
                                    label="Identifier"
                                    bind:value={
                                        vm.form.organizations[organization.id]
                                            .identifier
                                    }
                                    errors={vm.errors[
                                        `organizations.${organization.id}.identifier`
                                    ]}
                                />
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <InputSelect
                                    label="Member Of"
                                    options={organization.assignment_options}
                                    bind:value={
                                        vm.form.organizations[organization.id]
                                            .assignment
                                    }
                                    errors={vm.errors[
                                        `organizations.${organization.id}.assignment`
                                    ]}
                                />
                            </div>
                        </div>
                        <InputContainer>
                            <InputSelect
                                label="Status"
                                options={memberships.status_options}
                                bind:value={
                                    vm.form.organizations[organization.id]
                                        .membership_status
                                }
                                errors={vm.errors[
                                    `organizations.${organization.id}.membership_status`
                                ]}
                            />
                        </InputContainer>
                        <SubmitButtonContainer>
                            <SubmitButton
                                label="Update"
                                submitting={vm.submitting}
                            />
                            <Button
                                label="Remove Membership"
                                btnclass="btn-outline-danger"
                                click={() =>
                                    vm.remove(
                                        organization.id,
                                        organization.name,
                                    )}
                            />
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
    {/each}
</SlimView>
