<!--
    Which clubs can sign up for the event, and how many troopers and handlers each may send.
-->
<script lang="ts">
    import InputText from "$lib/components/form/InputText.svelte";
    import { t } from "$lib/i18n";
    import type { OverviewViewModel } from "../models";

    let { vm }: { vm: OverviewViewModel } = $props();
</script>

<div class="mt-3">
    {#each vm.form.organizations as organization (organization.id)}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex gap-3 mb-3 align-items-start">
                    <img
                        src={organization.image_url}
                        alt=""
                        width="48"
                        height="48"
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
