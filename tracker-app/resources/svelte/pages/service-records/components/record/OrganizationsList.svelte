<!-- The clubs the trooper belongs to: a table on wider screens, stacked cards on phones. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { TrooperOrganization } from "../../models/record-types";

    let { organizations }: { organizations: TrooperOrganization[] } = $props();
</script>

{#if organizations.length > 0}
    <div class="d-none d-md-block table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th class="logo-col"></th>
                    <th>{t("Organization")}</th>
                    <th>{t("Identifier")}</th>
                    <th>{t("Membership ID")}</th>
                    <th>{t("Assignment Location")}</th>
                </tr>
            </thead>
            <tbody>
                {#each organizations as organization (organization.id)}
                    <tr>
                        <td><img src={organization.logo_url} alt={t("Logo")} width="32" height="32" /></td>
                        <td class="fw-semibold">{organization.name}</td>
                        <td>{organization.identifier_label}</td>
                        <td class="font-monospace text-warning">{organization.identifier ?? ""}</td>
                        <td>{organization.assignment ?? t("Unassigned")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>

    <div class="d-block d-md-none">
        <div class="row g-3">
            {#each organizations as organization (organization.id)}
                <div class="col-12">
                    <div class="card bg-dark border-secondary shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0 bg-secondary-subtle p-1 rounded">
                                    <img src={organization.logo_url} alt={t("Logo")} width="36" height="36" />
                                </div>
                                <div class="flex-grow-1 min-w-0 ms-3">
                                    <small class="text-muted d-block text-uppercase fw-bold">{t("Organization")}</small>
                                    <span class="h6 mb-0 fw-bold text-white text-truncate d-block">
                                        {organization.name}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-secondary-subtle p-2 rounded mb-2">
                                <small class="text-muted d-block text-uppercase fw-bold">
                                    {organization.identifier_label}
                                </small>
                                <span class="font-monospace fw-bold text-warning small text-break">
                                    {organization.identifier ?? ""}
                                </span>
                            </div>
                            <div class="pt-2 border-top border-secondary mt-2">
                                <small class="text-muted d-block text-uppercase fw-bold">{t("Assignment")}</small>
                                <span class="small text-white text-break">
                                    <i class="fa-solid fa-location-dot me-1 text-muted small"></i>
                                    {organization.assignment ?? t("Unassigned")}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            {/each}
        </div>
    </div>
{:else}
    <div class="table-responsive">
        <table class="table">
            <tbody>
                <tr>
                    <td class="text-center py-4">{t("No Organization Memberships ... Yet!")}</td>
                </tr>
            </tbody>
        </table>
    </div>
{/if}

<style>
    .logo-col {
        width: 40px;
    }
</style>
