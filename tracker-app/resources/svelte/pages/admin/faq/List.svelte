<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { Link, usePage } from "@inertiajs/svelte";
    import ItemRow from "./components/ItemRow.svelte";
    import SectionFilterPills from "./components/SectionFilterPills.svelte";
    import { FaqListViewModel } from "./models";
    import type { FaqItem, FaqSection, Paginated } from "./models/types";

    interface PageData {
        items: FaqItem[] | Paginated<FaqItem>;
        sections: FaqSection[];
        section_id: number | null;
        sortable: boolean;
    }

    const page = usePage<PageData>();

    pageState.title = "FAQ Items";
    breadCrumbState.home("Command Staff", getRoute("admin.display")).add("FAQ", getRoute("admin.faq.list"));

    let vm = $derived(new FaqListViewModel(page.props.section_id, page.props.sortable));

    let rows = $derived(
        Array.isArray(page.props.items) ? page.props.items : page.props.items.data,
    );
    let links = $derived(
        Array.isArray(page.props.items) ? [] : page.props.items.links,
    );
</script>

<div class="row mb-3">
    <div class="col-sm-12 col-md-8">
        <SectionFilterPills sections={page.props.sections} section_id={page.props.section_id} />
    </div>
    <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0 d-flex align-items-center gap-2 justify-content-end">
        <Link href={getRoute("admin.faq.sections.list")} class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-fw fa-folder me-1"></i>
            Sections
        </Link>
        <Link
            href={getRoute("admin.faq.create", page.props.section_id ? { section_id: page.props.section_id } : {})}
            class="btn btn-sm btn-outline-success"
        >
            <i class="fa fa-fw fa-add me-1"></i>
            FAQ Item
        </Link>
    </div>
</div>

{#if vm.sortable}
    <p class="text-muted small mb-2">
        <i class="fa fa-fw fa-grip-vertical me-1"></i>
        Drag rows to reorder.
    </p>
{/if}

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                {#if vm.sortable}
                    <th style="width: 30px;"></th>
                {/if}
                <th>Section</th>
                <th>Title</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        {#if vm.sortable}
            <tbody use:sortable={{ handle: ".faq-drag-handle", onReorder: vm.reorder }}>
                {#each rows as item (item.id)}
                    <ItemRow {item} sortable={true} onDelete={vm.confirmDelete} />
                {/each}
            </tbody>
        {:else}
            <tbody>
                {#each rows as item (item.id)}
                    <ItemRow {item} sortable={false} onDelete={vm.confirmDelete} />
                {/each}
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">
                        <div class="d-flex gap-1 flex-wrap">
                            {#each links as link (link.label)}
                                {#if link.url}
                                    <Link href={link.url} class="btn btn-sm {link.active ? 'btn-secondary' : 'btn-outline-secondary'}">
                                        {@html link.label}
                                    </Link>
                                {:else}
                                    <span class="btn btn-sm btn-outline-secondary disabled">{@html link.label}</span>
                                {/if}
                            {/each}
                        </div>
                    </td>
                </tr>
            </tfoot>
        {/if}
    </table>
</div>

<Modal
    bind:show={vm.show}
    title="Delete FAQ Item"
    canClose={vm.cancelDelete}
>
    <p>Delete "{vm.deleting?.title}"? This cannot be undone.</p>
    <form onsubmit={vm.delete}>
        <div class="row mt-4">
            <div class="col text-end">
                <SubmitButton label="Delete" danger={true} submitting={vm.submitting} />
                <CancelButton click={vm.cancelDelete} />
            </div>
        </div>
    </form>
</Modal>
