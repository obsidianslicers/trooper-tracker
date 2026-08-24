<script lang="ts">
    import { getRoute } from "$lib/utils";
    import { Link } from "@inertiajs/svelte";
    import type { FaqSection } from "../models/types";

    interface Props {
        sections: FaqSection[];
        section_id: number | null;
    }

    let { sections, section_id }: Props = $props();
</script>

<div class="d-flex flex-wrap gap-2">
    <Link
        href={getRoute("admin.faq.list")}
        class="btn btn-sm {section_id === null ? 'btn-secondary' : 'btn-outline-secondary'}"
    >
        All
    </Link>
    {#each sections as section (section.id)}
        <Link
            href={getRoute("admin.faq.list", { section_id: section.id })}
            class="btn btn-sm {section_id === section.id ? 'btn-secondary' : 'btn-outline-secondary'}"
        >
            <i class="fa fa-fw {section.icon} me-1"></i>
            {section.label}
        </Link>
    {/each}
</div>
