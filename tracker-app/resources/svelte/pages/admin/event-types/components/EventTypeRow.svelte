<script lang="ts">
    import ActionMenu from "$lib/components/ui/actionmenus/ActionMenu.svelte";
    import ActionMenuDelete from "$lib/components/ui/actionmenus/ActionMenuDelete.svelte";
    import ActionMenuUpdate from "$lib/components/ui/actionmenus/ActionMenuUpdate.svelte";
    import type { EventTypeRow, IndexViewModel } from "../models";

    interface Props {
        vm: IndexViewModel;
        event_type: EventTypeRow;
    }

    let { vm, event_type }: Props = $props();
</script>

<tr data-id={event_type.id} class={["draggable", !event_type.is_active && "text-muted"]}>
    <td>
        <i class="fa fa-fw fa-grip-vertical move-handle grab"></i>
    </td>
    <td>
        <a href={event_type.update_route} class="text-reset">{event_type.name}</a>
        {#if !event_type.is_active}
            <span class="badge text-bg-secondary ms-1">Hidden</span>
        {/if}
        {#if event_type.description}
            <div class="small text-muted">{event_type.description}</div>
        {/if}
    </td>
    <td>
        {#if event_type.is_counted}
            <i class="fa fa-fw fa-check text-success" aria-hidden="true"></i>
            <span class="visually-hidden">Counts</span>
        {:else}
            <span class="badge text-bg-warning">Doesn't count</span>
        {/if}
    </td>
    <td class="text-muted small">{event_type.events_count}</td>
    <td class="text-end">
        <ActionMenu>
            <ActionMenuUpdate href={event_type.update_route} />
            {#if event_type.can_delete}
                <ActionMenuDelete click={() => (vm.delete_event_type = event_type)} />
            {/if}
        </ActionMenu>
    </td>
</tr>
