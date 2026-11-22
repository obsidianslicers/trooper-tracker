<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { CostumeApprovalsPageData } from "./models/review-types";
    import { ReviewQueueViewModel } from "./models/vms/ReviewQueueViewModel.svelte";

    const page = usePage<CostumeApprovalsPageData>();

    pageState.title = t("Costume Approvals");

    const vm = new ReviewQueueViewModel();
</script>

<SlimView wide={true}>
    <p class="text-muted">
        {t("Troopers added these costumes themselves. Approve a costume once and the trooper can use it for that club from then on.")}
    </p>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>{t("Trooper")}</th>
                    <th>{t("Costume")}</th>
                    <th>{t("Club")}</th>
                    <th>{t("Requested")}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {#each page.props.trooper_costumes as row (row.id)}
                    {@const busy = vm.isWorking(`costume-${row.id}`)}
                    <tr>
                        <td>
                            <a href={getRoute("admin.troopers.profile", { trooper: row.trooper.id, tab: "costumes" })}>
                                {row.trooper.name}
                            </a>
                        </td>
                        <td>{row.costume}</td>
                        <td>{row.club}</td>
                        <td class="text-nowrap">{row.requested_at}</td>
                        <td class="text-end text-nowrap">
                            <button
                                type="button"
                                class="btn btn-sm btn-success"
                                disabled={busy}
                                onclick={() => vm.approveCostume(row.id)}
                            >
                                {t("Approve")}
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                disabled={busy}
                                onclick={() => vm.denyCostume(row.id)}
                            >
                                {t("Deny")}
                            </button>
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="5" class="text-muted">{t("No costumes are waiting for review.")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</SlimView>
