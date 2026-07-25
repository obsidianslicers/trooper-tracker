<script lang="ts">
    import InputError from "$lib/components/form/InputError.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import {
        TrooperPickerViewModel,
        type Trooper,
    } from "$lib/domains/ui/TrooperPickerViewModel.svelte";

    interface Props {
        selected?: Trooper | null;
        label?: string | null;
        placeholder?: string | null;
        button_label?: string;
        modal_title?: string;
        search_placeholder?: string;
        debounce_ms?: number;
        disabled?: boolean;
        errors?: string | string[];
        clearable?: boolean;
        onSelect?: ((trooper: Trooper) => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        selected = $bindable<Trooper | null>(null),
        label = null,
        placeholder = "No trooper selected",
        button_label = "Pick Trooper",
        modal_title = "Select Trooper",
        search_placeholder = "Search troopers by name, TK ID, or email",
        debounce_ms = 350,
        disabled = false,
        errors = [],
        clearable = true,
        onSelect = null,
    }: Props = $props();

    const vm = new TrooperPickerViewModel();

    const floating_label = $derived(vm.options.label ?? "Trooper");

    $effect(() => {
        vm.setOptions({
            label,
            placeholder: placeholder ?? "No trooper selected",
            button_label,
            modal_title,
            search_placeholder,
            debounce_ms,
            disabled,
            errors,
            clearable,
            onSelect,
        });
    });

    $effect(() => {
        if (!vm.show_modal) {
            vm.clearPendingSearch();
            return;
        }

        const query = vm.term.trim();

        if (query.length === 0) {
            vm.resetSearchForEmptyQuery();
            return;
        }

        vm.scheduleSearch(query);

        return () => {
            vm.clearPendingSearch();
        };
    });
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
                value={vm.selected_trooper?.legal_name ??
                    vm.options.placeholder}
                placeholder={vm.options.placeholder}
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
            <label for={id}>{floating_label}</label>
        </div>

        {#if vm.options.clearable && selected}
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
    <div class="trooper-picker-modal">
        <div class="mb-3">
            <label class="form-label" for="trooper-search">Search</label>
            <input
                id="trooper-search"
                type="text"
                class="form-control"
                placeholder={vm.options.search_placeholder}
                bind:value={vm.term}
                autocomplete="off"
            />
        </div>

        {#if vm.is_loading}
            <p class="text-muted mb-3">Searching troopers...</p>
        {/if}

        {#if vm.error_message.length > 0}
            <div class="alert alert-danger" role="alert">
                {vm.error_message}
            </div>
        {/if}

        {#if !vm.is_loading && vm.term.trim().length > 0 && vm.troopers.length === 0}
            <p class="text-danger mb-0">No troopers matched your search.</p>
        {/if}

        {#if vm.troopers.length > 0}
            <div class="list-group">
                {#each vm.troopers as trooper (trooper.id)}
                    <button
                        type="button"
                        class="list-group-item list-group-item-action text-start"
                        onclick={() => (selected = vm.selectTrooper(trooper))}
                    >
                        <div class="fw-semibold">
                            {trooper.legal_name}
                        </div>
                    </button>
                {/each}
            </div>
        {/if}

        <div class="d-flex justify-content-end mt-3">
            <button
                type="button"
                class="btn btn-secondary"
                onclick={() => vm.closeModal()}
            >
                Cancel
            </button>
        </div>
    </div>
</Modal>
