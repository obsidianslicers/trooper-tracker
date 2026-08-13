<script lang="ts">
    import InputText from "$lib/components/form/InputText.svelte";
    import type { CostumesViewModel } from "$lib/domains/account/vms/CostumesViewModel.svelte.ts";

    // // Props received from Laravel Controller
    // let { search_results = [] } = $props();

    // // Local UI state

    // // Inertia Form for submission
    // const form = useForm({
    //     organization_costume_ids: [],
    // });

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

    <!-- Organization Selection
        {#if selectedCostume}
            <div class="mb-4">
                <label class="form-label font-weight-bold">
                    Select Approved Organizations
                </label>

                <div class="list-group">
                    {#if selectedCostume.organization_costumes?.length > 0}
                        {#each selectedCostume.organization_costumes as org (org.id)}
                            <label
                                class="list-group-item d-flex align-items-center py-3 pointer"
                            >
                                <input
                                    type="checkbox"
                                    class="form-check-input me-3"
                                    value={org.id}
                                    bind:group={form.organization_costume_ids}
                                />
                                <div>
                                    <span class="d-block"
                                        >{org.organization.name}</span
                                    >
                                </div>
                            </label>
                        {/each}
                    {:else}
                        <div class="text-danger small">
                            No organizations found for this costume type.
                        </div>
                    {/if}
                </div>
            </div>
        {/if}
    // Submit Button
    <div class="row">
        <div class="col text-end">
            <button
                type="submit"
                class="btn btn-primary"
                disabled={!selectedCostume ||
                    form.organization_costume_ids.length === 0 ||
                    form.processing}
            >
                {form.processing ? "Saving..." : "Add to Armory"}
            </button>
        </div>
    </div>
         -->
</div>
