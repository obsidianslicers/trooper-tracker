<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import { FaqSectionFormViewModel } from "../models";
    import type { FaqSection } from "../models/types";
    import Form from "./components/Form.svelte";

    interface PageData {
        section: FaqSection & {
            created_at: string | null;
            updated_at: string | null;
            created_by?: { legal_name: string } | null;
            updated_by?: { legal_name: string } | null;
        };
    }

    const page = usePage<PageData>();

    pageState.title = "Update FAQ Section";
    breadCrumbState
        .home("Command Staff", getRoute("admin.display"))
        .add("FAQ", getRoute("admin.faq.list"))
        .add("Sections", getRoute("admin.faq.sections.list"));

    let vm = $derived(new FaqSectionFormViewModel("update", page.props.section));
</script>

<Form {vm} />

<div class="row">
    <div class="col-12 text-end">
        <span class="text-muted small">
            {#if page.props.section.created_at === page.props.section.updated_at}
                created
                {#if page.props.section.created_by}
                    by {page.props.section.created_by.legal_name}
                {/if}
            {:else}
                updated
                {#if page.props.section.updated_by}
                    by {page.props.section.updated_by.legal_name}
                {/if}
            {/if}
        </span>
    </div>
</div>
