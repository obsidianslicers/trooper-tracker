<script lang="ts">
    import { t } from "$lib/i18n";
    import Alert from "$lib/components/ui/Alert.svelte";
    import { MembershipsViewModel } from "../models";

    interface Props {
        vm: MembershipsViewModel;
    }
    let { vm }: Props = $props();
</script>

<h6 class="mb-3">{t("Organization Memberships")}</h6>

{#if vm.organization_memberships.length === 0}
    <Alert>{t("No organization memberships are currently linked to you.")}</Alert>
{:else}
    <table class="table table-striped">
        <tbody>
            {#each vm.organization_memberships as membership}
                <tr>
                    <td>
                        <img
                            src={membership.image_url}
                            alt=""
                            width="24"
                            height="24"
                            class="me-2"
                        />
                        <span>
                            {membership.membership_path}
                        </span>
                    </td>
                    <td>
                        {#if membership.membership_status == "pending"}
                            <i class="fa fa-fw fa-clock text-warning me-2"></i>
                        {:else}
                            <i
                                class="fa fa-fw fa-circle-check text-success me-2"
                            ></i>
                        {/if}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
{/if}
