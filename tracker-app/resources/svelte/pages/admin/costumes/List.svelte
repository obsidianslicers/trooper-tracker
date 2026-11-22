<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { ListViewModel, type ListPageData } from "./models";

    const page = usePage<ListPageData>();

    pageState.title = t("Costumes");

    let vm = new ListViewModel(page.props);
</script>

<div class="row mb-3">
    <div class="col-sm-12 col-md-6">
        <form class="input-group" onsubmit={vm.search} role="search">
            <input
                type="text"
                class="form-control rounded-start"
                placeholder={t("Search Name (at least 3 chars)")}
                aria-label={t("Search Name (at least 3 chars)")}
                bind:value={vm.search_term}
            />
            <button type="submit" class="btn btn-outline-secondary" aria-label={t("Search")}>
                <i class="fa fa-fw fa-search"></i>
            </button>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>{t("Name")}</th>
                <th class="text-end">
                    <CreateButton label={t("Costume")} href={getRoute("admin.costumes.create")} small={true} />
                </th>
            </tr>
        </thead>
        <tbody>
            {#each vm.costumes as costume (costume.id)}
                <tr>
                    <td>
                        {costume.name}
                        <br />
                        <i class="text-muted small">{costume.organizations}</i>
                    </td>
                    <td>
                        <ActionMenu>
                            <ActionMenuUpdate href={vm.updateUrl(costume)} />
                            {#if costume.can_delete}
                                <ActionMenuDelete href={vm.deleteUrl(costume)} />
                            {/if}
                        </ActionMenu>
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-muted">{t("No costumes to show.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Pagination links={page.props.costumes.meta.links} />
