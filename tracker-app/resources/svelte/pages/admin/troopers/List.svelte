<script lang="ts">
    import { t } from "$lib/i18n";
    import OrganizationPicker from "$lib/components/OrganizationPicker.svelte";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { ListTroopersPageData } from "./models/list-types";
    import { ListTroopersViewModel } from "./models/vms/ListTroopersViewModel.svelte";

    const page = usePage<ListTroopersPageData>();

    pageState.title = t("Troopers");

    let vm = new ListTroopersViewModel(page.props);
</script>

<div class="row mb-3 gy-2">
    <div class="col-sm-12 col-md-6">
        <form class="input-group" onsubmit={vm.search} role="search">
            <input
                type="text"
                class="form-control"
                placeholder={t("Search Name, Legal Name, Email (at least 3 chars)")}
                aria-label={t("Search troopers")}
                bind:value={vm.search_term}
            />
            <button type="submit" class="btn btn-outline-secondary" aria-label={t("Search")}>
                <i class="fa fa-fw fa-search"></i>
            </button>
        </form>

        <div class="mt-2">
            <OrganizationPicker
                options={page.props.organization_options}
                label={t("Group")}
                placeholder={t("All Groups")}
                bind:value={() => vm.organization_id, (value) => (vm.organization_id = value as number | null)}
            />
        </div>

        {#if page.props.organization}
            <div class="mt-2">
                <span class="px-3 py-2 badge rounded-pill bg-primary">
                    <a class="text-white" href={vm.listUrl({ organization_id: null })}>
                        {page.props.organization.name}
                        <span class="ps-2"><i class="fa fa-fw fa-times"></i></span>
                    </a>
                </span>
            </div>
        {/if}
    </div>
    <div class="col-sm-12 col-md-6 text-end d-flex flex-wrap justify-content-end gap-2 align-items-start">
        <a href={getRoute("admin.troopers.recruit.create")} class="btn btn-outline-primary btn-sm">
            <i class="fa fa-fw fa-user-plus"></i>
            {t("Recruit Trooper")}
        </a>
        <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label={t("Role")}>
            <a
                href={vm.listUrl({ membership_role: null })}
                class={["btn btn-outline-primary", !vm.filters.membership_role && "active"]}
            >
                {t("All")}
            </a>
            {#each page.props.roles as role (role.value)}
                <a
                    href={vm.listUrl({ membership_role: role.value })}
                    class={["btn btn-outline-primary", vm.filters.membership_role === role.value && "active"]}
                >
                    {role.label}
                </a>
            {/each}
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                {#each page.props.sort_columns as column (column.key)}
                    <th>
                        <a href={vm.sortUrl(column.key)}>
                            {column.label} <i class={["fa fa-fw", vm.sortIcon(column.key)]}></i>
                        </a>
                    </th>
                {/each}
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.troopers.data as trooper (trooper.id)}
                <tr>
                    <td class="text-nowrap">
                        {#if trooper.is_active}
                            <i class="fa fa-fw fa-check text-success pe-2"></i>
                        {:else}
                            <i class="fa fa-fw fa-times text-danger pe-2"></i>
                        {/if}
                        {trooper.display_name}
                        {#if trooper.is_invalid}
                            <span class="badge bg-secondary ms-1">{t("Created in Error")}</span>
                        {/if}
                        {#if trooper.is_departed}
                            <span class="badge bg-dark border border-warning text-warning ms-1">
                                {t("In Memoriam")}
                            </span>
                        {/if}
                        <br />
                        <span class="text-muted">{trooper.legal_name}</span>
                        <br />
                        {#if trooper.email}
                            {trooper.email}
                        {:else}
                            <span class="text-muted">{t("( missing email )")}</span>
                        {/if}
                    </td>
                    <td>
                        <a href={vm.listUrl({ membership_role: trooper.role.value })}>{trooper.role.label}</a>
                    </td>
                    <td>{trooper.status}</td>
                    <td class={[trooper.is_long_gone && "text-muted"]}>{trooper.last_seen ?? "-"}</td>
                    <td>
                        <ActionMenu>
                            <ActionMenuUpdate href={vm.profileUrl(trooper.id)} />
                        </ActionMenu>
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="5" class="text-muted">{t("No troopers matched your search.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Pagination links={page.props.troopers.meta.links} />
