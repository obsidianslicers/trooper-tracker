<script lang="ts">
    import breadCrumbState from "$lib/states/bread-crumb-state.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { FaqSectionFormViewModel } from "../models";
    import Form from "./components/Form.svelte";

    pageState.title = "Create FAQ Section";
    breadCrumbState
        .home("Command Staff", getRoute("admin.display"))
        .add("FAQ", getRoute("admin.faq.list"))
        .add("Sections", getRoute("admin.faq.sections.list"));

    let vm = new FaqSectionFormViewModel("create");
</script>

<Form {vm} />
