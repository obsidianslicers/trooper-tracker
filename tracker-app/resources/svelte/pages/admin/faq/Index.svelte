<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import ItemRow from "./components/ItemRow.svelte";
    import SectionRow from "./components/SectionRow.svelte";
    import { IndexViewModel, type IndexPageData } from "./models";

    const page = usePage<IndexPageData>();

    pageState.title = "FAQs";

    let vm = new IndexViewModel(page.props);
</script>

<p class="text-muted small mb-2">
    <i class="fa fa-fw fa-grip-vertical me-1"></i>
    Drag rows to reorder
    {#if vm.showing_sections}
        the FAQ Sections
    {:else}
        the FAQs
    {/if}
</p>

<div class="table-responsive mt-3">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 120px;">Re-Order</th>
                <th style="width: 120px;">Expand FAQs</th>
                <th colspan="2">Title</th>
                <th style="width: 120px;">FAQs</th>
                <th class="text-end">
                    <CreateButton
                        label="Section"
                        href={getRoute("admin.faq.sections.create")}
                        small={true}
                        disabled={!vm.showing_sections}
                    />
                </th>
            </tr>
        </thead>
        <tbody {@attach sortable({ onReorderComplete: vm.reorder })}>
            {#each vm.sections as section (section.id)}
                <SectionRow {vm} {section} />
                {#if vm.showing_items && vm.expand_section_id === section.id}
                    <tr>
                        <th></th>
                        <th></th>
                        <th colspan="2"></th>
                        <th></th>
                        <th class="text-end">
                            <CreateButton
                                label="Item"
                                small={true}
                                href={getRoute("admin.faq.items.create")}
                            />
                        </th>
                    </tr>
                    {#each section.faqs as faq (faq.id)}
                        <ItemRow {vm} item={faq} />
                    {/each}
                {/if}
            {/each}
        </tbody>
    </table>
</div>

<!-- <DeleteConfirmationModal
    show={vm.show_delete_confirmation}
    label={vm.delete_section?.label}
    deleting={vm.deleting}
    onDelete={vm.delete}
    onCancel={() => (vm.delete_section = null)}
/> -->
