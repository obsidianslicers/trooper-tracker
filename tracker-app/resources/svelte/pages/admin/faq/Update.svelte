<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import Form from "./components/Form.svelte";
    import { FaqFormViewModel } from "./models";
    import type { FaqItem, FaqSectionOption } from "./models/types";

    interface PageData {
        faq: FaqItem & {
            created_at: string | null;
            updated_at: string | null;
            created_by?: { legal_name: string } | null;
            updated_by?: { legal_name: string } | null;
        };
        sections: FaqSectionOption[];
        breadcrumbs: { title: string; url: string }[];
    }

    const page = usePage<PageData>();

    pageState.title = "Update FAQ Item";
    page.props.breadcrumbs.forEach((crumb, index) => {
        if (index === 0) {
            breadCrumbState.home(crumb.title, crumb.url);
        } else {
            breadCrumbState.add(crumb.title, crumb.url);
        }
    });

    let vm = $derived(new FaqFormViewModel("update", page.props.faq));
</script>

<Form {vm} sections={page.props.sections} />

<div class="row">
    <div class="col-12 text-end">
        <span class="text-muted small">
            {#if page.props.faq.created_at === page.props.faq.updated_at}
                created
                {#if page.props.faq.created_by}
                    by {page.props.faq.created_by.legal_name}
                {/if}
            {:else}
                updated
                {#if page.props.faq.updated_by}
                    by {page.props.faq.updated_by.legal_name}
                {/if}
            {/if}
        </span>
    </div>
</div>
