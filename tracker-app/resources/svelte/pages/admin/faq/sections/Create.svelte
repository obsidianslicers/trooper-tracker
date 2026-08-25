<script lang="ts">
    import pageState from "$lib/states/page-state.svelte";
    import { FaqSectionFormViewModel } from "../models";
    import Form from "./components/Form.svelte";

    pageState.title = "Create FAQ Section";

    let vm = new FaqSectionFormViewModel("create");
</script>

<Form {vm} />
