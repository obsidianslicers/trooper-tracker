<script lang="ts">
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import type { FaqItem, IndexViewModel } from "../models";

    interface Props {
        vm: IndexViewModel;
        item: FaqItem;
    }
    let { vm, item }: Props = $props();
</script>

<tr data-id={item.id} class="draggable">
    <td class="text-muted text-center">
        <i class="fa fa-fw fa-grip-vertical move-handle grab"></i>
    </td>
    <td></td>
    <td class="text-center">
        {#if item.has_video}
            <i class="fa fa-fw fa-circle-play text-info ms-1"></i>
        {/if}
    </td>
    <td>{item.title}</td>
    <td></td>
    <td>
        <ActionMenu>
            <ActionMenuUpdate href={item.update_route} />
            <ActionMenuDelete click={() => (vm.delete_item = item)} />
        </ActionMenu>
    </td>
</tr>
