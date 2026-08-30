<script lang="ts">
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import type { FaqSection, ListSectionsViewModel } from "../models";

    interface Props {
        vm: ListSectionsViewModel;
        section: FaqSection;
    }

    let { vm, section }: Props = $props();
</script>

<tr data-id={section.id} style="cursor: grab;">
    <td class="text-muted faq-section-drag-handle" style="cursor: grab;">
        <i class="fa fa-fw fa-grip-vertical"></i>
    </td>
    <td class="text-center">
        <i class="fa fa-fw {section.icon} text-muted"></i>
    </td>
    <td>{section.label}</td>
    <td class="text-muted small">{section.faqs_count}</td>
    <td>
        <ActionMenu>
            <ActionMenuUpdate href={section.update_route} />
            <ActionMenuDelete click={() => (vm.selected_section = section)} />
        </ActionMenu>
    </td>
</tr>
