<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import ItemForm from "./components/ItemForm.svelte";
    import { CreateItemViewModel } from "./models";

    pageState.title = "Create FAQ Item";

    let vm = new CreateItemViewModel();
</script>

<SlimView>
    <ItemForm {vm} label="Create" />
</SlimView>
