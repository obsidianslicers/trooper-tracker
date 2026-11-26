<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuLink from "$lib/components/ui/actionmenus/ActionMenuLink.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { ListPageData } from "./models/list-types";
    import { ListEventsViewModel } from "./models/vms/ListEventsViewModel.svelte";

    const page = usePage<ListPageData>();

    pageState.title = t("Events / Troops");

    let vm = new ListEventsViewModel(page.props);
</script>

<div class="row mb-3 gy-2">
    <div class="col-sm-12 col-md-6">
        <form class="input-group" onsubmit={vm.search} role="search">
            <input
                type="text"
                class="form-control"
                placeholder={t("Search Event Name (at least 3 chars)")}
                aria-label={t("Search events")}
                bind:value={vm.search_term}
            />
            <button type="submit" class="btn btn-outline-secondary" aria-label={t("Search")}>
                <i class="fa fa-fw fa-search"></i>
            </button>
        </form>
        {#if page.props.organization}
            <div class="m-3">
                <span class="px-3 py-2 badge rounded-pill bg-primary">
                    <a class="text-white" href={vm.listUrl({ organization_id: null })}>
                        {page.props.organization.name}
                        <span class="ps-2"><i class="fa fa-fw fa-times"></i></span>
                    </a>
                </span>
            </div>
        {/if}
    </div>
    <div class="col-sm-12 col-md-6 text-end">
        <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label={t("Status")}>
            <a
                href={vm.listUrl({ status: null })}
                class={["btn btn-outline-primary", !vm.filters.status && "active"]}
            >
                {t("All")}
            </a>
            {#each page.props.statuses as status (status.value)}
                <a
                    href={vm.listUrl({ status: status.value })}
                    class={["btn btn-outline-primary", vm.filters.status === status.value && "active"]}
                >
                    {status.label}
                </a>
            {/each}
        </div>
    </div>
</div>

<!-- above the table, which scrolls sideways on phones and would hide it -->
<div class="text-end mb-2">
    <CreateButton label={t("Event")} href={vm.create_url} small={true} />
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>{t("Name")}</th>
                <th>{t("Shifts")}</th>
                <th>{t("Organization")}</th>
                <th>{t("Status")}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each page.props.events.data as event (event.id)}
                <tr>
                    <td><img src={event.logo_url} alt="" width="32" height="32" /></td>
                    <td>
                        <a href={getRoute("events.display", { event: event.id })}>{event.name}</a>
                        <br />
                        <i class="text-muted small">{event.time_display}</i>
                    </td>
                    <td>{event.shift_count}</td>
                    <td>
                        <a href={vm.listUrl({ organization_id: event.organization.id })}>{event.organization.name}</a>
                    </td>
                    <td>
                        <a href={vm.listUrl({ status: event.status.value })}>{event.status.label}</a>
                    </td>
                    <td class="text-end">
                        {#if event.can_update}
                            <ActionMenu>
                                <ActionMenuUpdate href={getRoute("admin.events.edit", { event: event.id })} />
                                <ActionMenuLink
                                    label={t("Copy")}
                                    icon="fa-copy"
                                    href={getRoute("admin.events.copy.create", { event: event.id })}
                                />
                            </ActionMenu>
                        {/if}
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="6" class="text-muted">{t("No events matched your search.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Pagination links={page.props.events.meta.links} />
