<script lang="ts">
    import { getRoute } from "$lib/utils";
    import { Link } from "@inertiajs/svelte";
    import type { FaqItem } from "../models/types";

    interface Props {
        item: FaqItem;
        sortable: boolean;
        onDelete: (item: FaqItem) => void;
    }

    let { item, sortable, onDelete }: Props = $props();
</script>

<tr data-id={item.id} style={sortable ? "cursor: grab;" : ""}>
    {#if sortable}
        <td class="text-muted faq-drag-handle" style="cursor: grab;">
            <i class="fa fa-fw fa-grip-vertical"></i>
        </td>
    {/if}
    <td class="text-nowrap">
        <i class="fa fa-fw {item.faq_section?.icon} text-muted me-1"></i>
        <span class="small text-muted">{item.faq_section?.label}</span>
        {#if item.video_url}
            <i class="fa fa-fw fa-circle-play text-info ms-1" title="Video"></i>
        {/if}
    </td>
    <td>{item.title}</td>
    {#if !sortable}
        <td class="text-muted small">{item.sort_order}</td>
    {/if}
    <td>
        <div class="dropdown float-end">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fa fa-fw fa-gear pe-3"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <Link class="dropdown-item" href={getRoute("admin.faq.update", { faq: item.id })}>
                        <i class="fa fa-fw fa-pencil text-primary me-3"></i>
                        Update
                    </Link>
                </li>
                <li>
                    <button type="button" class="dropdown-item" onclick={() => onDelete(item)}>
                        <i class="fa fa-fw fa-times text-danger me-3"></i>
                        Delete
                    </button>
                </li>
            </ul>
        </div>
    </td>
</tr>
