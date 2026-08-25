<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { FaqSectionFormViewModel } from "../models";
    import Form from "./components/Form.svelte";

    interface PageData {
        breadcrumbs: { title: string; url: string }[];
    }

    const page = usePage<PageData>();

    pageState.title = "Create FAQ Section";
    page.props.breadcrumbs.forEach((crumb, index) => {
        if (index === 0) {
            breadCrumbState.home(crumb.title, crumb.url);
        } else {
            breadCrumbState.add(crumb.title, crumb.url);
        }
    });

    let vm = new FaqSectionFormViewModel("create");
</script>

<Form {vm} />
