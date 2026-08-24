<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { Link, usePage } from "@inertiajs/svelte";
    import { FaqSectionListViewModel } from "../models";
    import type { FaqSection } from "../models/types";
    import SectionRow from "./components/SectionRow.svelte";

    interface PageData {
        sections: FaqSection[];
    }

    const page = usePage<PageData>();

    pageState.title = "FAQ Sections";
    breadCrumbState
        .home("Command Staff", getRoute("admin.display"))
        .add("FAQ", getRoute("admin.faq.list"))
        .add("Sections", getRoute("admin.faq.sections.list"));

    let vm = new FaqSectionListViewModel();
</script>

<div class="row mb-3">
    <div class="col-sm-12 col-md-8">
        <Link href={getRoute("admin.faq.list")} class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-fw fa-arrow-left me-1"></i>
            FAQ Items
        </Link>
    </div>
    <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0">
        <Link href={getRoute("admin.faq.sections.create")} class="btn btn-sm btn-outline-success">
            <i class="fa fa-fw fa-add me-1"></i>
            Section
        </Link>
    </div>
</div>

<p class="text-muted small mb-2">
    <i class="fa fa-fw fa-grip-vertical me-1"></i>
    Drag rows to reorder.
</p>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 30px;"></th>
                <th style="width: 40px;"></th>
                <th>Label</th>
                <th style="width: 80px;">Items</th>
                <th style="width: 40px;"></th>
            </tr>
        </thead>
        <tbody use:sortable={{ handle: ".faq-section-drag-handle", onReorder: vm.reorder }}>
            {#each page.props.sections as section (section.id)}
                <SectionRow {section} onDelete={vm.confirmDelete} />
            {/each}
        </tbody>
    </table>
</div>

<Modal
    bind:show={vm.show}
    title="Delete Section"
    canClose={vm.cancelDelete}
>
    <p>Delete section "{vm.deleting?.label}"? This cannot be undone.</p>
    <form onsubmit={vm.delete}>
        <div class="row mt-4">
            <div class="col text-end">
                <SubmitButton label="Delete" danger={true} submitting={vm.submitting} />
                <CancelButton click={vm.cancelDelete} />
            </div>
        </div>
    </form>
</Modal>
