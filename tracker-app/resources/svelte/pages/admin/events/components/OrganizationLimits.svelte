<!--
    Which clubs can sign up for the event, and how many troopers and handlers each may send.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import InputError from "$lib/components/form/InputError.svelte";
    import type { OverviewViewModel } from "../models";

    let { vm }: { vm: OverviewViewModel } = $props();
</script>

<div class="table-responsive mt-3">
    <table class="table table-sm align-middle">
        <thead>
            <tr>
                <th colspan="2">{t("Costume Organization Requested")}</th>
                <th class="text-center">{t("Can Sign Up")}</th>
                <th>{t("Limit Troopers")}</th>
                <th>{t("Limit Handlers")}</th>
            </tr>
        </thead>
        <tbody>
            {#each vm.form.organizations as organization (organization.id)}
                <tr>
                    <td>
                        <img src={organization.image_url} alt="" width="32" height="32" />
                    </td>
                    <td>
                        <label for="org-{organization.id}-can-attend">{organization.name}</label>
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
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            aria-label={t("Limit Troopers")}
                            placeholder={t("unlimited")}
                            disabled={!organization.can_attend}
                            bind:value={organization.troopers_allowed}
                        />
                        <InputError
                            errors={vm.organizationError(organization.id, "troopers_allowed")}
                        />
                    </td>
                    <td>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            aria-label={t("Limit Handlers")}
                            placeholder={t("unlimited")}
                            disabled={!organization.can_attend}
                            bind:value={organization.handlers_allowed}
                        />
                        <InputError
                            errors={vm.organizationError(organization.id, "handlers_allowed")}
                        />
                    </td>
                </tr>
            {/each}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-muted small">
                    {t("** Troopers will be placed on stand-by when an organization limit is reached. Troopers must select their organization when signing up.")}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
