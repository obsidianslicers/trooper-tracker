<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import type { RequestPermissionsViewModel } from "../../models/vms";

    interface Props {
        vm: RequestPermissionsViewModel;
    }

    let { vm }: Props = $props();
</script>

<InputContainer>
    <h6>Organizations to Moderate</h6>
    {#each vm.organizations as organization (organization.id)}
        <div style="padding-left: {organization.depth * 1.25}rem">
            {#if vm.isModerating(organization.id)}
                <InputCheckbox
                    label="{organization.name} (already moderates)"
                    checked={true}
                    disabled={true}
                />
            {:else}
                <InputCheckbox
                    label={organization.name}
                    checked={vm.form.moderated_organization_ids.includes(organization.id)}
                    onchange={() => vm.toggleOrganization(organization.id)}
                />
            {/if}
        </div>
    {:else}
        <p class="text-muted small">You do not moderate any organizations.</p>
    {/each}
    <InputError errors={vm.errors.moderated_organization_ids} />
</InputContainer>
