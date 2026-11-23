<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { UpdateViewModel, type UpdatePageData } from "./models";

    const page = usePage<UpdatePageData>();

    pageState.title = t("Update Costume");

    let vm = new UpdateViewModel(page.props);
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <InputContainer>
            <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
        </InputContainer>

        <p class="mb-2">{t("Assigned Organizations")}</p>
        <div class="ps-3">
            {#each vm.organizations as organization (organization.id)}
                <InputContainer>
                    <InputCheckbox
                        label={organization.name}
                        checked={vm.isOffered(organization.id)}
                        onchange={() => vm.setOffered(organization.id, !vm.isOffered(organization.id))}
                    />
                </InputContainer>
            {/each}
        </div>

        <SubmitButtonContainer>
            <SubmitButton label={t("Update")} submitting={vm.submitting} />
            <CancelButton href={getRoute("admin.costumes.index")} />
            {#if vm.can_delete}
                <span class="ms-2"><DeleteButton click={vm.confirmDelete} /></span>
            {/if}
        </SubmitButtonContainer>
    </form>
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>

{#if vm.can_delete}
    <ConfirmationModal
        show={vm.show_delete}
        title={t("Delete Costume")}
        message={t("Are you sure you want to delete :name?", { name: page.props.costume.name })}
        confirmLabel={t("Delete")}
        danger={true}
        submitting={vm.deleting}
        onConfirm={vm.delete}
        onCancel={vm.cancelDelete}
    >
        {#if vm.deletion_impact && (vm.deletion_impact.event_troopers > 0 || vm.deletion_impact.trooper_costumes > 0)}
            <div class="alert alert-warning">
                <ul class="mb-0">
                    {#if vm.deletion_impact.event_troopers > 0}
                        <li>
                            {t(":count event registration will have its costume reset to N/A. Historical troop credit will be preserved.|:count event registrations will have their costume reset to N/A. Historical troop credit will be preserved.", {}, vm.deletion_impact.event_troopers)}
                        </li>
                    {/if}
                    {#if vm.deletion_impact.trooper_costumes > 0}
                        <li>
                            {t(":count trooper costume will be removed from trooper profiles.|:count trooper costumes will be removed from trooper profiles.", {}, vm.deletion_impact.trooper_costumes)}
                        </li>
                    {/if}
                </ul>
            </div>
        {/if}
    </ConfirmationModal>
{/if}
