<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
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

    let vm = $derived(new FaqFormViewModel("create", null, page.props.section_id));
</script>

<Form {vm} sections={page.props.sections} />
