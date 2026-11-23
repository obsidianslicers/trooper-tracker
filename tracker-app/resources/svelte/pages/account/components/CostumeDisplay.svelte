<script lang="ts">
    import { t } from "$lib/i18n";
    import Alert from "$lib/components/ui/Alert.svelte";
    import DeleteButton from "$lib/components/ui/buttons/DeleteButton.svelte";
    import { CostumesViewModel } from "../models";

    interface Props {
        vm: CostumesViewModel;
    }
    let { vm }: Props = $props();
</script>

{#if vm.trooper_costumes.length === 0}
    <Alert>
        <b class="mb-3">{t("No Attached Costumes ... Yet!")}</b>
        <br />
        <i>
            {t('To attach a costume to your armory, find your costume, then select your organization/club, and click "Add to Armory".')}
        </i>
    </Alert>
{:else}
    <table class="table table-striped">
        <tbody>
            {#each vm.trooper_costumes as trooper_costume}
                <tr>
                    <td>
                        {trooper_costume.name}
                        <br />
                        <span class="text-muted small">
                            {trooper_costume.costume_organizations}
                        </span>
                        {#if trooper_costume.pending_organizations?.length > 0}
                            <br />
                            <span class="badge text-bg-warning">
                                {t("Pending officer review: :organizations", {
                                    organizations: trooper_costume.pending_organizations.join(", "),
                                })}
                            </span>
                        {/if}
                    </td>
                    <td class="text-end">
                        <DeleteButton
                            label={null}
                            submitting={trooper_costume.submitting}
                            click={() => vm.removeCostume(trooper_costume)}
                        />
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
{/if}
