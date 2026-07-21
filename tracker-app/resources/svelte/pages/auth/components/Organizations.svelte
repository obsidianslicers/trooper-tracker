<script lang="ts">
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type { RegisterViewModel } from "$lib/domains/auth";

    interface PageProps {
        vm: RegisterViewModel;
    }

    let { vm }: PageProps = $props();

    // Derived visibility states driven by the viewmodel data
    const isVisitor = $derived(vm.form.account_type === "visitor");
    const isHandler = $derived(vm.form.account_type === "handler");
    const isMember = $derived(vm.form.account_type === "member");
</script>

<p>Select your associated organizations below.</p>
<InputContainer>
    <InputError errors={vm.errors["organizations"]} />
</InputContainer>
{#each vm.organizations as organization (organization.id)}
    <div class="mb-4">
        <div class="mb-2">
            <InputCheckbox
                label={organization.name}
                bind:checked={vm.form.organizations[organization.id].selected}
                change={() => vm.toggleOrganization(organization.id)}
            />
        </div>

        {#if vm.isOrganizationSelected(organization.id)}
            <div class="ps-4 border-start">
                {#if isMember}
                    <InputContainer>
                        <InputText
                            bind:value={
                                vm.form.organizations[organization.id]
                                    .identifier
                            }
                            errors={vm.getErrors(organization.id, "identifier")}
                            label={organization.identifier_display}
                        />
                    </InputContainer>
                {/if}

                {#if !isVisitor}
                    {#if organization.regions && organization.regions.length > 0}
                        <div class="mb-3">
                            <InputSelect
                                label="Region/Garrison"
                                placeholder="Select your Region/Garrison"
                                disabled={isVisitor}
                                bind:value={
                                    vm.form.organizations[organization.id]
                                        .region_id
                                }
                                change={() =>
                                    vm.resetOnRegionChange(organization.id)}
                                options={vm.getRegions(organization.id)}
                                errors={vm.getErrors(
                                    organization.id,
                                    "region_id",
                                )}
                            />
                        </div>

                        <div class="mb-3">
                            <InputSelect
                                label="Unit/Squad"
                                placeholder="Select your Unit/Squad"
                                disabled={!vm.form.organizations[
                                    organization.id
                                ].region_id || isVisitor}
                                bind:value={
                                    vm.form.organizations[organization.id]
                                        .unit_id
                                }
                                options={vm.getUnits(organization.id)}
                                errors={vm.getErrors(
                                    organization.id,
                                    "unit_id",
                                )}
                            />
                        </div>
                    {/if}
                {/if}
            </div>
        {/if}
    </div>
{/each}
