<script lang="ts">
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import ItemForm from "./components/ItemForm.svelte";
    import { type UpdateItemPageData, UpdateItemViewModel } from "./models";

    const page = usePage<UpdateItemPageData>();

    pageState.title = "Update FAQ Item";

    let vm = new UpdateItemViewModel(page.props);
</script>

<SlimView>
    <ItemForm {vm} label="Update" section_options={vm.section_options} />
    <TrooperStamps trooper_stamps={vm.trooper_stamps} />
</SlimView>
