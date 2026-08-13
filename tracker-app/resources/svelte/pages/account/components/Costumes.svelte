<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import {
        CostumesViewModel,
        type CostumesPageData,
    } from "$lib/domains/account";
    import CostumeDisplay from "./CostumeDisplay.svelte";
    import CostumeSearch from "./CostumeSearch.svelte";

    interface Props {
        costumes: CostumesPageData;
    }
    let { costumes }: Props = $props();

    let vm = new CostumesViewModel(costumes);
</script>

<SlimView>
    <CostumeSearch {vm} />
    <CostumeDisplay {vm} />
</SlimView>
