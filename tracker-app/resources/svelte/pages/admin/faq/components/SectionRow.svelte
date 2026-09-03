<script lang="ts">
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import type { FaqSection, IndexViewModel } from "../models";

    interface Props {
        vm: IndexViewModel;
        section: FaqSection;
    }
    let { vm, section }: Props = $props();
</script>

{#if vm.showing_sections || vm.expand_section_id === section.id}
    <tr
        data-id={section.id}
        class={vm.showing_sections ? "draggable" : "table-primary"}
    >
        <td class="text-muted">
            {#if vm.showing_sections}
                <i class="fa fa-fw fa-grip-vertical move-handle grab"></i>
            {/if}
        </td>
        <td class="text-muted">
            <i
                class={[
                    "fa fa-fw",
                    vm.showing_items ? "fa-caret-down" : "fa-caret-right",
                    "d-block pointer",
                ]}
                onclick={() => vm.toggleSection(section.id)}
            ></i>
        </td>
        <td class="text-center">
            <i class="fa fa-fw {section.icon} text-muted"></i>
        </td>
        <td>
            <div
                class="d-block pointer"
                onclick={() => vm.toggleSection(section.id)}
            >
                {section.label}
            </div>
        </td>
        <td class="text-muted small">{section.faqs.length}</td>
        <td>
            {#if vm.showing_sections}
                <ActionMenu>
                    <ActionMenuUpdate href={section.update_route} />
                    <ActionMenuDelete
                        click={() => (vm.delete_section = section)}
                    />
                </ActionMenu>
            {/if}
        </td>
    </tr>
{/if}
