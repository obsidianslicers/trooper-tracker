<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import Form from "./components/Form.svelte";
    import { FaqFormViewModel } from "./models";
    import type { FaqSectionOption } from "./models/types";

    interface PageData {
        section_id: number | null;
        sections: FaqSectionOption[];
        breadcrumbs: { title: string; url: string }[];
    }

    const page = usePage<PageData>();

    pageState.title = "Create FAQ Item";
    page.props.breadcrumbs.forEach((crumb, index) => {
        if (index === 0) {
            breadCrumbState.home(crumb.title, crumb.url);
        } else {
            breadCrumbState.add(crumb.title, crumb.url);
        }
    });

    let vm = $derived(new FaqFormViewModel("create", null, page.props.section_id));
</script>

<Form {vm} sections={page.props.sections} />
