<!--
    The Costume Stats Lookup: type part of a costume's name and pick it to see its rankings.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { CostumeOption } from "../models";
    import { CostumeSearchViewModel } from "../models";

    interface Props {
        costumes: CostumeOption[];
        onpick: (costume: CostumeOption) => void;
    }
    let { costumes, onpick }: Props = $props();

    const vm = $derived(new CostumeSearchViewModel(costumes));

    function pick(costume: CostumeOption) {
        vm.choose(costume);
        onpick(costume);
    }
</script>

<div class="card shadow-sm border-0">
    <div class="card-header bg-dark text-white text-uppercase small fw-bold">
        <i class="fa-solid fa-magnifying-glass me-2"></i>
        {t("Costume Stats Lookup")}
    </div>
    <div class="card-body">
        <div class="position-relative lookup">
            <label class="form-label" for="costume-search">{t("Search Costumes")}</label>
            <input
                id="costume-search"
                type="text"
                class="form-control"
                placeholder={t("Type a costume name...")}
                autocomplete="off"
                bind:value={vm.search}
                onfocus={() => (vm.showResults = true)}
                onblur={() => (vm.showResults = false)}
            />

            {#if vm.showResults && vm.matches.length > 0}
                <div class="list-group position-absolute w-100 mt-1 shadow-lg results">
                    {#each vm.matches as costume (costume.id)}
                        <button
                            type="button"
                            class="list-group-item list-group-item-action bg-dark text-white border-secondary"
                            onmousedown={(e) => e.preventDefault()}
                            onclick={() => pick(costume)}
                        >
                            {costume.name}
                        </button>
                    {/each}
                </div>
            {/if}
        </div>
    </div>
</div>

<style>
    .lookup {
        max-width: 480px;
    }
    .results {
        z-index: 1050;
        max-height: 260px;
        overflow-y: auto;
    }
</style>
