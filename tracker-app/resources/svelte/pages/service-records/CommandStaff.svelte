<!-- Who is on Command Staff and which clubs they moderate. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import EmptyList from "./components/lists/EmptyList.svelte";
    import ListHeader from "./components/lists/ListHeader.svelte";
    import type { CommandStaffPageData } from "./models/list-types";

    let { troopers }: CommandStaffPageData = $props();

    pageState.title = t("Command Staff");
</script>

<div class="container-fluid py-4">
    <ListHeader title={t("Command Staff")} icon="fa-brands fa-empire" />

    {#if troopers.length > 0}
        <div class="d-none d-md-block table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{t("Trooper")}</th>
                        <th>{t("Role")}</th>
                        <th>{t("CS Organizations")}</th>
                    </tr>
                </thead>
                <tbody>
                    {#each troopers as trooper (trooper.id)}
                        <tr>
                            <td><a href={trooper.url} class="fw-semibold">{trooper.display_name}</a></td>
                            <td>{trooper.role}</td>
                            <td>
                                {#if trooper.organizations}
                                    <span class="text-break">{trooper.organizations}</span>
                                {:else}
                                    <span class="text-muted">—</span>
                                {/if}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <div class="d-block d-md-none">
            <div class="row g-3">
                {#each troopers as trooper (trooper.id)}
                    <div class="col-12">
                        <div class="card bg-dark border-secondary shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <a href={trooper.url} class="h6 mb-0 text-decoration-none fw-bold">{trooper.display_name}</a>
                                    <span class="badge bg-secondary text-uppercase">{trooper.role}</span>
                                </div>
                                <div class="pt-2 border-top border-secondary mt-2">
                                    <small class="text-muted d-block text-uppercase fw-bold mb-1">{t("CS Organizations")}</small>
                                    <p class="card-text text-white small mb-0 text-break">
                                        {#if trooper.organizations}
                                            {trooper.organizations}
                                        {:else}
                                            <span class="text-muted small fst-italic">{t("None listed")}</span>
                                        {/if}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    {:else}
        <EmptyList message={t("No command staff found.")} />
    {/if}
</div>
