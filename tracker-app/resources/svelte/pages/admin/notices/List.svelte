<script lang="ts">
    import { t } from "$lib/i18n";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuLink from "$lib/components/ui/actionmenus/ActionMenuLink.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import Pagination from "$lib/components/ui/Pagination.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { ListViewModel, type ListPageData, type NoticeScope } from "./models";

    const page = usePage<ListPageData>();

    pageState.title = t("Notice to the Troopers");

    let vm = new ListViewModel(page.props);

    const scopes: { scope: NoticeScope; label: string }[] = [
        { scope: "active", label: t("Active") },
        { scope: "past", label: t("Past") },
        { scope: "future", label: t("Future") },
    ];
</script>

<div class="row mb-3">
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
    <div class="col-sm-12 col-md-6 text-end">
        <div class="btn-group" role="group" aria-label={t("Show notices")}>
            {#each scopes as item (item.scope)}
                <a
                    href={vm.listUrl({ scope: item.scope })}
                    class={["btn btn-outline-primary", vm.scope === item.scope && "active"]}
                    aria-current={vm.scope === item.scope ? "page" : undefined}
                >
                    {item.label}
                </a>
            {/each}
        </div>
    </div>
</div>

<!-- above the table, which scrolls sideways on phones and would hide it -->
<div class="text-end mb-2">
    <CreateButton label={t("Notice")} href={vm.create_url} small={true} />
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th style="width: 36px;"></th>
                <th>{t("Title")}</th>
                <th>{t("Organization")}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            {#each vm.notices as notice (notice.id)}
                <tr>
                    <td>
                        <img src={notice.logo_url} alt="" width="32" height="32" />
                    </td>
                    <td>
                        <i class={["fa fa-fw fa-circle-info me-2", `text-${notice.type}`]}></i>
                        {notice.title}
                    </td>
                    <td>
                        {#if notice.organization}
                            <a href={vm.listUrl({ organization_id: notice.organization.id })}>
                                {notice.organization.name}
                            </a>
                        {:else}
                            {t("Everyone")}
                        {/if}
                    </td>
                    <td>
                        {#if notice.can_update}
                            <ActionMenu>
                                <ActionMenuUpdate href={vm.updateUrl(notice)} />
                                <ActionMenuLink
                                    label={t("Copy")}
                                    icon="fa-copy"
                                    href={vm.copyUrl(notice)}
                                />
                            </ActionMenu>
                        {/if}
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="4" class="text-muted">{t("No notices to show.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<Pagination links={page.props.notices.meta.links} />
