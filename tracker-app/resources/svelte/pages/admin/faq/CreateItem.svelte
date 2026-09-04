<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import ItemForm from "./components/ItemForm.svelte";
    import type { CreateItemPageData } from "./models";
    import { CreateItemViewModel } from "./models";

    const page = usePage<CreateItemPageData>();

    pageState.title = "Create FAQ Item";

    let vm = new CreateItemViewModel(page.props);
</script>

<SlimView>
    <ItemForm {vm} label="Create" section_options={vm.section_options} />
</SlimView>
