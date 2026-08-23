<script lang="ts">
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import type { CostumesViewModel } from "../models";

    interface Props {
        vm: CostumesViewModel;
    }

    let { vm }: Props = $props();
</script>

<div class="mb-4">
    <!-- Server-Searched Input -->
    <div class="mb-4 position-relative">
        <InputText
            label="Find Your Costume"
            placeholder="Search ... (i.e. Shadow Scout)"
            bind:value={vm.search_term}
            oninput={vm.searchCostumes}
            onfocus={vm.showSearchResults}
            onblur={vm.hideSearchResults}
            searching={vm.searching}
        />

        <!-- Server Results Dropdown -->
        {#if vm.show_results && vm.search_results.length > 0}
            <div
                class="list-group position-absolute w-100 mt-1 shadow-lg search-results"
            >
                {#each vm.search_results as costume (costume.id)}
                    <button
                        type="button"
                        class="list-group-item list-group-item-action bg-dark text-white border-secondary"
                        onclick={() => vm.selectCostume(costume)}
                    >
                        {costume.name}
                    </button>
                {/each}
            </div>
        {/if}
    </div>

    {#if vm.selected_costume}
        <div class="mb-4">
            <p>Select Approved Organizations</p>

            {#if vm.selected_costume.organizations?.length === 0}
                <div class="text-danger small">
                    No organizations found for this costume type.
                </div>
            {:else}
                <div class="list-group">
                    {#each vm.selected_costume.organizations as org (org.id)}
                        <label
                            class="list-group-item d-flex align-items-center py-3 pointer"
                        >
                            <input
                                type="checkbox"
                                class="form-check-input me-3"
                                bind:checked={org.selected}
                            />
                            <div>
                                <span class="d-block">{org.name}</span>
                            </div>
                        </label>
                    {/each}
                </div>
                <SubmitButtonContainer>
                    <SubmitButton
                        label="Add to Armory"
                        submitting={vm.submitting}
                        disabled={!vm.canAddCostume()}
                        click={vm.addCostume}
                    />
                </SubmitButtonContainer>
            {/if}
        </div>
    {/if}
</div>
