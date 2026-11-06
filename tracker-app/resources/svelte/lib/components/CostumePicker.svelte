<script lang="ts">
    import InputError from "$lib/components/form/InputError.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import {
        CostumePickerViewModel,
        type PickerCostume,
    } from "$lib/components/models/CostumePickerViewModel.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";

    interface Props {
        selected?: PickerCostume | null;
        label?: string | null;
        placeholder?: string;
        modal_title?: string;
        disabled?: boolean;
        errors?: string | string[];
        onSelected?: ((costume: PickerCostume | null) => void) | null;
    }

    const id = "id-" + crypto.randomUUID();
    const search_id = "search-" + id;

    let {
        selected = $bindable<PickerCostume | null>(null),
        label = null,
        placeholder = "No costume selected",
        modal_title = "Select Costume",
        disabled = false,
        errors = [],
        onSelected = null,
    }: Props = $props();

    const vm = new CostumePickerViewModel(() => ({
        label,
        placeholder,
        modal_title,
        disabled,
        errors,
        onSelected,
    }));

    $effect(() => {
        if (!vm.show_modal) {
            vm.clearPendingSearch();
            return;
        }

        vm.scheduleSearch(vm.term.trim());

        return () => vm.clearPendingSearch();
    });

    function organizationNames(costume: PickerCostume): string {
        return costume.organizations.map((o) => o.name).join(", ");
    }
</script>

<div>
    <div class="input-group">
        <div class="form-floating flex-grow-1">
            <input
                {id}
                type="text"
                class={[
                    "form-control pointer",
                    vm.options.errors.length > 0 ? "is-invalid" : "",
                ]}
                value={selected
                    ? `${selected.name} (ID: ${selected.id})`
                    : vm.options.placeholder}
                readonly
                disabled={vm.options.disabled}
                onclick={() => vm.openModal()}
                onkeydown={(event) => {
                    if (event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        vm.openModal();
                    }
                }}
            />
            <label for={id}>{vm.options.label ?? "Costume"}</label>
        </div>

        {#if selected}
            <button
                type="button"
                class="btn btn-outline-secondary"
                disabled={vm.options.disabled}
                onclick={() => (selected = vm.clearSelection())}
            >
                Clear
            </button>
        {/if}
    </div>

    <InputError errors={vm.options.errors} />
</div>

<Modal
    bind:show={vm.show_modal}
    title={vm.options.modal_title}
    canClose={() => vm.closeModal()}
>
    <div class="mb-3">
        <label class="form-label" for={search_id}>Search</label>
        <input
            id={search_id}
            type="text"
            class="form-control"
            placeholder="Search costumes by name"
            bind:value={vm.term}
            autocomplete="off"
        />
    </div>

    {#if vm.is_loading}
        <p class="text-muted mb-3">
            <i class="fa fa-fw fa-spinner fa-spin"></i>
            Searching costumes...
        </p>
    {/if}

    {#if vm.error_message.length > 0}
        <div class="alert alert-danger" role="alert">{vm.error_message}</div>
    {/if}

    {#if !vm.is_loading && vm.term.trim().length >= 2 && vm.costumes.length === 0}
        <p class="text-danger mb-0">No costumes matched your search.</p>
    {/if}

    {#if vm.costumes.length > 0}
        <div class="list-group">
            {#each vm.costumes as costume (costume.id)}
                <button
                    type="button"
                    class="list-group-item list-group-item-action text-start"
                    onclick={() => (selected = vm.selectCostume(costume))}
                >
                    <div class="fw-semibold">{costume.name}</div>
                    <div class="small text-muted">
                        ID: {costume.id}
                        {#if costume.organizations.length > 0}
                            · {organizationNames(costume)}
                        {/if}
                    </div>
                </button>
            {/each}
        </div>
    {/if}

    <SubmitButtonContainer>
        <CancelButton click={() => vm.closeModal()} />
    </SubmitButtonContainer>
</Modal>
