<script lang="ts">
    import { untrack } from "svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { CostumesViewModel, type CostumesPageData } from "../models";
    import CostumeDisplay from "./CostumeDisplay.svelte";
    import CostumeSearch from "./CostumeSearch.svelte";

    interface Props {
        costumes: CostumesPageData;
    }
    let { costumes }: Props = $props();

    let vm = new CostumesViewModel(untrack(() => costumes));
</script>

<SlimView>
    <CostumeSearch {vm} />
    <CostumeDisplay {vm} />
</SlimView>
