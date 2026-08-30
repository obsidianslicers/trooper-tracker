<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { Link, usePage } from "@inertiajs/svelte";
    import SectionRow from "./components/SectionRow.svelte";
    import { ListSectionsViewModel, type ListSectionsPageData } from "./models";

    const page = usePage<ListSectionsPageData>();

    pageState.title = "FAQ Sections";

    let vm = new ListSectionsViewModel(page.props);
</script>

<div class="row mb-3">
    <div class="col-sm-12 col-md-8">
        <Link
            href={getRoute("admin.faq.list")}
            class="btn btn-sm btn-outline-secondary"
        >
            <i class="fa fa-fw fa-arrow-left me-1"></i>
            FAQ Items
        </Link>
    </div>
    <div class="col-sm-12 col-md-4 text-end mt-2 mt-md-0">
        <Link
            href={getRoute("admin.faq.sections.create")}
            class="btn btn-sm btn-outline-success"
        >
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
        <tbody
            use:sortable={{
                handle: ".faq-section-drag-handle",
                onReorder: vm.reorder,
            }}
        >
            {#each vm.sections as section (section.id)}
                <SectionRow {vm} {section} />
            {/each}
        </tbody>
    </table>
</div>

<Modal
    bind:show={vm.show_delete_confirmation}
    title="Delete Section"
    canClose={vm.cancelDelete}
>
    <p>Delete section "{vm.selected_section?.label}"? This cannot be undone.</p>
    <form onsubmit={vm.delete}>
        <SubmitButtonContainer>
            <SubmitButton
                label="Delete"
                danger={true}
                submitting={vm.deleting}
            />
            <CancelButton click={() => (vm.selected_section = null)} />
        </SubmitButtonContainer>
    </form>
</Modal>
