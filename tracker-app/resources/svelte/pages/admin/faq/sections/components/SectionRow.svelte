<script lang="ts">
    import { getRoute } from "$lib/utils";
    import { Link } from "@inertiajs/svelte";
    import type { FaqSection } from "../../models/types";

    interface Props {
        section: FaqSection;
        onDelete: (section: FaqSection) => void;
    }

    let { section, onDelete }: Props = $props();
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
        <div class="dropdown float-end">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fa fa-fw fa-gear pe-3"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <Link class="dropdown-item" href={getRoute("admin.faq.sections.update", { section: section.id })}>
                        <i class="fa fa-fw fa-pencil text-primary me-3"></i>
                        Update
                    </Link>
                </li>
                <li>
                    <button type="button" class="dropdown-item" onclick={() => onDelete(section)}>
                        <i class="fa fa-fw fa-times text-danger me-3"></i>
                        Delete
                    </button>
                </li>
            </ul>
        </div>
    </td>
</tr>
