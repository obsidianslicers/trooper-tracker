<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { untrack } from "svelte";
    import { ProfileAuthorityViewModel, type ProfileAuthorityPageData } from "../../models";

    interface Props {
        trooper_id: number;
        authority: ProfileAuthorityPageData;
    }
    let { trooper_id, authority }: Props = $props();

    let vm = new ProfileAuthorityViewModel(
        untrack(() => trooper_id),
        untrack(() => authority),
    );
</script>

<SlimView>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputSelect
                label="Role"
                options={authority.role_options}
                bind:value={vm.form.membership_role}
                errors={vm.errors.membership_role}
            />
        </InputContainer>

        {#if vm.is_moderator_role}
            <table class="table table-sm">
                <thead>
                    <tr><th>Organization</th><th>Moderator</th><th>Notify</th></tr>
                </thead>
                <tbody>
                    {#each vm.organizations as organization (organization.id)}
                        {@const covered = vm.isCoveredByParent(organization)}
                        <tr>
                            <td style="padding-left: {organization.depth * 1.25}rem">
                                {organization.name}
                            </td>
                            <td>
                                <InputCheckbox
                                    disabled={covered}
                                    checked={covered || vm.form.organizations[organization.id].is_moderator}
                                    onchange={() =>
                                        (vm.form.organizations[organization.id].is_moderator =
                                            !vm.form.organizations[organization.id].is_moderator)}
                                />
                            </td>
                            <td>
                                <InputCheckbox bind:checked={vm.form.organizations[organization.id].should_notify} />
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        {/if}

        <SubmitButtonContainer>
            <SubmitButton label="Update Authority" submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>
</SlimView>
