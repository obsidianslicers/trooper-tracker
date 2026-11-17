<!--
    Which clubs can sign up for the event, and how many troopers and handlers each may send.
-->
<script lang="ts">
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import { t } from "$lib/i18n";
    import type { OverviewViewModel } from "../models";

    let { vm }: { vm: OverviewViewModel } = $props();
</script>

<div class="mt-3">
    <div class="d-none d-lg-block">
        <!-- Desktop table view -->
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th colspan="2"
                            >{t("Costume Organization Requested")}</th
                        >
                        <th class="text-center">{t("Can Sign Up")}</th>
                        <th>{t("Limit Troopers")}</th>
                        <th>{t("Limit Handlers")}</th>
                    </tr>
                </thead>
                <tbody>
                    {#each vm.form.organizations as organization (organization.id)}
                        <tr>
                            <td>
                                <img
                                    src={organization.image_url}
                                    alt=""
                                    width="32"
                                    height="32"
                                />
                            </td>
                            <td>
                                <label for="org-{organization.id}-can-attend"
                                    >{organization.name}</label
                                >
                            </td>
                            <td class="text-center">
                                <input
                                    id="org-{organization.id}-can-attend"
                                    type="checkbox"
                                    class="form-check-input"
                                    bind:checked={organization.can_attend}
                                />
                            </td>
                            <td>
                                <InputText
                                    bare={true}
                                    placeholder={t("unlimited")}
                                    disabled={!organization.can_attend}
                                    bind:value={organization.troopers_allowed}
                                    errors={vm.organizationError(
                                        organization.id,
                                        "troopers_allowed",
                                    )}
                                />
                            </td>
                            <td>
                                <InputText
                                    bare={true}
                                    placeholder={t("unlimited")}
                                    disabled={!organization.can_attend}
                                    bind:value={organization.handlers_allowed}
                                    errors={vm.organizationError(
                                        organization.id,
                                        "handlers_allowed",
                                    )}
                                />
                            </td>
                        </tr>
                    {/each}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-muted small">
                            {t(
                                "** Troopers will be placed on stand-by when an organization limit is reached. Troopers must select their organization when signing up.",
                            )}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Mobile card view -->
    <div class="d-lg-none">
        {#each vm.form.organizations as organization (organization.id)}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3 align-items-start">
                        <img
                            src={organization.image_url}
                            alt=""
                            width="16"
                            height="16"
                            class="rounded"
                        />
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-0">{organization.name}</h6>
                        </div>
                        <div class="form-check form-switch">
                            <input
                                id="org-{organization.id}-can-attend"
                                type="checkbox"
                                class="form-check-input"
                                bind:checked={organization.can_attend}
                            />
                            <label
                                class="form-check-label"
                                for="org-{organization.id}-can-attend"
                            >
                                {t("Can Sign Up")}
                            </label>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <InputText
                                label={t("Limit Troopers")}
                                placeholder={t("unlimited")}
                                disabled={!organization.can_attend}
                                bind:value={organization.troopers_allowed}
                                errors={vm.organizationError(
                                    organization.id,
                                    "troopers_allowed",
                                )}
                            />
                            {#if !organization.troopers_allowed}
                                <InputHelp>
                                    {t("unlimited")}
                                </InputHelp>
                            {/if}
                        </div>
                        <div class="col-6">
                            <InputText
                                label={t("Limit Handlers")}
                                placeholder={t("unlimited")}
                                disabled={!organization.can_attend}
                                bind:value={organization.handlers_allowed}
                                errors={vm.organizationError(
                                    organization.id,
                                    "handlers_allowed",
                                )}
                            />
                            {#if !organization.handlers_allowed}
                                <InputHelp>
                                    {t("unlimited")}
                                </InputHelp>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        {/each}
        <div class="alert alert-info small mb-0">
            <i class="fa fa-fw fa-info-circle"></i>
            {t(
                "** Troopers will be placed on stand-by when an organization limit is reached. Troopers must select their organization when signing up.",
            )}
        </div>
    </div>
</div>
