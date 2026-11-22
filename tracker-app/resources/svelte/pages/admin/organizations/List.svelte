<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuLink from "$lib/components/ui/actionmenus/ActionMenuLink.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { ListPageData } from "./models";

    const page = usePage<ListPageData>();

    pageState.title = t("Organizations");
</script>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>{t("Name")}</th>
                <th>{t("Synchronized")}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.organizations as organization (organization.id)}
                <tr>
                    <td><img src={organization.logo_url} alt="" width="32" height="32" /></td>
                    <td>
                        <span style="padding-left: {organization.depth * 1.25}rem">{organization.name}</span>
                    </td>
                    <td>{organization.synchronized_at ?? ""}</td>
                    <td>
                        {#if organization.can_manage}
                            <ActionMenu>
                                {#if organization.add_child_label}
                                    <ActionMenuLink
                                        label={organization.add_child_label}
                                        icon="fa-plus"
                                        href={getRoute("admin.organizations.create", { parent: organization.id })}
                                    />
                                {/if}
                                {#if organization.has_costumes}
                                    <ActionMenuLink
                                        label={t("Costumes")}
                                        icon="fa-shirt"
                                        href={getRoute("admin.organizations.costumes", { organization: organization.id })}
                                    />
                                {/if}
                                <ActionMenuUpdate
                                    href={getRoute("admin.organizations.update", { organization: organization.id })}
                                />
                                <li><hr class="dropdown-divider" /></li>
                                <ActionMenuLink
                                    label={t("Add Notice")}
                                    icon="fa-add"
                                    href={getRoute("admin.notices.create", { organization_id: organization.id })}
                                />
                                <ActionMenuLink
                                    label={t("Notices")}
                                    icon="fa-circle-info"
                                    href={getRoute("admin.notices.index", { organization_id: organization.id })}
                                />
                            </ActionMenu>
                        {/if}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
