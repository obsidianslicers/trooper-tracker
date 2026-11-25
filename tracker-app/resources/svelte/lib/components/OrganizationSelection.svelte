<!--
    Pick clubs, then an ID, region and unit in each: registration and the denied application's
    resubmit form.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type {
        OrganizationSelectionForm,
        OrganizationSelectionViewModel,
    } from "./models/OrganizationSelectionViewModel.svelte";

    interface Props {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        vm: OrganizationSelectionViewModel<any, OrganizationSelectionForm>;
        /** The account type: visitors pick no region or unit, handlers give no ID. */
        role: string | null;
    }

    let { vm, role }: Props = $props();

    const isVisitor = $derived(role === "visitor");
    const isMember = $derived(role === "member");
</script>

<p>{t("Select your associated organizations below.")}</p>
<InputContainer>
    <InputError errors={vm.errors["organizations"]} />
</InputContainer>
{#each vm.organizations as organization (organization.id)}
    <div class="mb-4">
        <div class="mb-2">
            <InputCheckbox
                label={organization.name}
                bind:checked={vm.form.organizations[organization.id].selected}
                onchange={() => vm.toggleOrganization(organization.id)}
            />
        </div>

        {#if vm.isOrganizationSelected(organization.id)}
            <div class="ps-4 border-start">
                {#if isMember || isVisitor}
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
                                label={t("Region/Garrison")}
                                placeholder={t("Select your Region/Garrison")}
                                disabled={isVisitor}
                                bind:value={
                                    vm.form.organizations[organization.id]
                                        .region_id
                                }
                                onchange={() =>
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
                                label={t("Unit/Squad")}
                                placeholder={t("Select your Unit/Squad")}
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
