<script lang="ts">
    import { t } from "$lib/i18n";
    import { MembershipsViewModel } from "../models";

    interface Props {
        vm: MembershipsViewModel;
    }
    let { vm }: Props = $props();
</script>

{#if vm.organization_requests.length !== 0}
    <h6 class="mb-3">{t("Organization Requests")}</h6>

    <table class="table table-striped">
        <tbody>
            {#each vm.organization_requests as request}
                <tr>
                    <td>
                        <img
                            src={request.image_url}
                            alt=""
                            width="24"
                            height="24"
                            class="me-2"
                        />
                        <span>
                            {request.membership_path}
                        </span>
                        {#if request.status == "denied" && request.denial_reason}
                            <br />
                            <span class="text-danger">
                                <i class="fa fa-fw fa-angles-right"></i>
                                {request.denial_reason}
                            </span>
                        {/if}
                        <br />
                        <i class="text-muted small">
                            {t("updated :when", { when: request.updated })}
                        </i>
                    </td>
                    <td class="text-end text-nowrap">
                        <i class="text-muted me-2">
                            {request.status}
                        </i>
                        {#if request.status == "pending"}
                            <i class="fa fa-fw fa-clock text-warning me-2"></i>
                        {:else if request.status == "denied"}
                            <i class="fa fa-fw fa-circle-xmark text-danger me-2"
                            ></i>
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
