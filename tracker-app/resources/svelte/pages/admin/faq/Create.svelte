<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import Form from "./components/Form.svelte";
    import { FaqFormViewModel } from "./models";
    import type { FaqSectionOption } from "./models/types";

    interface PageData {
        section_id: number | null;
        sections: FaqSectionOption[];
    }

    const page = usePage<PageData>();

    pageState.title = "Create FAQ Item";
    breadCrumbState.home("Command Staff", getRoute("admin.display")).add("FAQ", getRoute("admin.faq.list"));

    let vm = $derived(new FaqFormViewModel("create", null, page.props.section_id));
</script>

<Form {vm} sections={page.props.sections} />
