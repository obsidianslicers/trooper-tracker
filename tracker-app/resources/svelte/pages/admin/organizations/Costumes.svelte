<script lang="ts">
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import OrganizationTabs from "./components/OrganizationTabs.svelte";
    import type { CostumesPageData } from "./models";

    const page = usePage<CostumesPageData>();

    pageState.title = t("Organizations");
</script>

<OrganizationTabs organization_id={page.props.organization.id} is_club={true} active="costumes" />

<div class="table-responsive mt-3">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>{t("Name")}</th>
                <th>{t("Synchronized")}</th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.costumes as costume (costume.id)}
                <tr>
                    <td>{costume.name}</td>
                    <td>{costume.synchronized_at ?? "-"}</td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-muted">{t("No costumes to show.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
