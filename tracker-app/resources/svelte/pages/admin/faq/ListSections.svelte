<script lang="ts">
    import { sortable } from "$lib/actions/sortable";
    import DeleteConfirmationModal from "$lib/components/DeleteConfirmationModal.svelte";
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import CreateButton from "$lib/components/ui/buttons/CreateButton.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { Link, usePage } from "@inertiajs/svelte";
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
                <th class="text-end">
                    <CreateButton
                        href={getRoute("admin.faq.sections.create")}
                    />
                </th>
            </tr>
        </thead>
        <tbody {@attach sortable({ onReorderComplete: vm.reorder })}>
            {#each vm.sections as section (section.id)}
                <tr data-id={section.id}>
                    <td class="text-muted">
                        <i class="fa fa-fw fa-grip-vertical move-handle grab"
                        ></i>
                    </td>
                    <td class="text-center">
                        <i class="fa fa-fw {section.icon} text-muted"></i>
                    </td>
                    <td>{section.label}</td>
                    <td class="text-muted small">{section.faqs_count}</td>
                    <td>
                        <ActionMenu>
                            <ActionMenuUpdate href={section.update_route} />
                            <ActionMenuDelete
                                click={() => (vm.delete_section = section)}
                            />
                        </ActionMenu>
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>

<DeleteConfirmationModal
    show={vm.show_delete_confirmation}
    label={vm.delete_section?.label}
    deleting={vm.deleting}
    onDelete={vm.delete}
    onCancel={() => (vm.delete_section = null)}
/>
