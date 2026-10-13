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
        <td>
            {#if vm.showing_sections}
                <i class="fa fa-fw fa-grip-vertical move-handle grab"></i>
            {/if}
        </td>
        <td>
            <button
                type="button"
                class="btn btn-link p-0 text-reset text-decoration-none d-block"
                aria-label="Toggle section items"
                onclick={() => vm.toggleSection(section.id)}
            >
                <i
                    class={[
                        "fa fa-fw",
                        vm.showing_items ? "fa-caret-down" : "fa-caret-right",
                    ]}
                    aria-hidden="true"
                ></i>
            </button>
        </td>
        <td class="text-center">
            <i class="fa fa-fw {section.icon}"></i>
        </td>
        <td>
            <button
                type="button"
                class="btn btn-link p-0 text-reset text-decoration-none d-block w-100 text-start"
                onclick={() => vm.toggleSection(section.id)}
            >
                {section.label}
            </button>
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
