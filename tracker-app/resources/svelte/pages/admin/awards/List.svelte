<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuLink from "$lib/components/ui/actionmenus/ActionMenuLink.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { ListViewModel, type ListPageData } from "./models";

    const page = usePage<ListPageData>();

    pageState.title = t("Troopers Awards");

    let vm = new ListViewModel(page.props);
</script>

<div class="row mb-3 gy-2">
    <div class="col-sm-12 col-md-6">
        <form class="input-group" onsubmit={vm.search} role="search">
            <input
                type="text"
                class="form-control rounded-start"
                placeholder={t("Search Title (at least 3 chars)")}
                aria-label={t("Search Title (at least 3 chars)")}
                bind:value={vm.search_term}
            />
            <button type="submit" class="btn btn-outline-secondary" aria-label={t("Search")}>
                <i class="fa fa-fw fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-sm-12 col-md-6">
        {#if vm.organization}
            <span class="px-3 py-2 badge rounded-pill bg-primary">
                <a class="text-white" href={vm.listUrl({ organization_id: null })}>
                    {vm.organization.name}
                    <span class="ps-2"><i class="fa fa-fw fa-times"></i></span>
                </a>
            </span>
        {/if}
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>{t("Title")}</th>
                <th>{t("Organization")}</th>
                <th class="text-end">{t("Troopers")}</th>
                <th class="text-end">
                    <CreateButton label={t("Award")} href={vm.create_url} small={true} />
                </th>
            </tr>
        </thead>
        <tbody>
            {#each vm.awards as award (award.id)}
                <tr>
                    <td><img src={award.logo_url} alt="" width="32" height="32" /></td>
                    <td>{award.name}</td>
                    <td>
                        {#if award.organization}
                            <a href={vm.listUrl({ organization_id: award.organization.id })}>
                                {award.organization.name}
                            </a>
                        {:else}
                            {t("Everyone")}
                        {/if}
                    </td>
                    <td class="text-end">{award.troopers_count}</td>
                    <td>
                        {#if award.can_update}
                            <ActionMenu>
                                <ActionMenuUpdate href={vm.updateUrl(award)} />
                                <ActionMenuLink label={t("Copy")} icon="fa-copy" href={vm.copyUrl(award)} />
                                <li><hr class="dropdown-divider" /></li>
                                <ActionMenuLink
                                    label={t("Troopers Awarded")}
                                    icon="fa-users"
                                    href={vm.troopersUrl(award)}
                                />
                            </ActionMenu>
                        {/if}
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="5" class="text-muted">{t("No awards to show.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Pagination links={page.props.awards.meta.links} />
