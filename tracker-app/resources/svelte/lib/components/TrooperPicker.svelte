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

    const selected_display_value = $derived(
        selected ? vm.getDisplayName(selected) : vm.options.placeholder,
    );

    const selected_subtitle = $derived(
        selected ? vm.getSubtitle(selected) : "",
    );

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

        const query = vm.search_query.trim();

        if (query.length === 0) {
            vm.resetSearchForEmptyQuery();
            return;
        }

        vm.scheduleSearch(query, searchTroopersStub);

        return () => {
            vm.clearPendingSearch();
        };
    });

    // TODO: Replace this stub with a real API call when backend endpoint is ready.
    async function searchTroopersStub(query: string): Promise<Trooper[]> {
        const delay_ms = 260;
        await new Promise((resolve) => setTimeout(resolve, delay_ms));

        const sample_troopers: Trooper[] = [
            {
                id: 1,
                tk_id: "TK-421",
                display_name: "Finn FN-2187",
                email: "fn2187@firstorder.example",
                membership_status: "active",
            },
            {
                id: 2,
                tk_id: "TI-182",
                display_name: "Iden Versio",
                email: "iden@inferno.example",
                membership_status: "active",
            },
            {
                id: 3,
                tk_id: "BH-2049",
                display_name: "Din Djarin",
                email: "din@mandalore.example",
                membership_status: "pending",
            },
            {
                id: 4,
                tk_id: "SL-001",
                display_name: "Leia Organa",
                email: "leia@alliance.example",
                membership_status: "active",
            },
        ];

        const lookup = query.toLowerCase();

        return sample_troopers.filter((trooper) => {
            const haystack = [
                String(trooper.id),
                String(trooper.tk_id ?? ""),
                String(trooper.display_name ?? trooper.name ?? ""),
                String(trooper.email ?? ""),
            ]
                .join(" ")
                .toLowerCase();

            return haystack.includes(lookup);
        });
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
                value={selected_display_value}
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

    {#if selected_subtitle.length > 0}
        <div class="form-text px-2">{selected_subtitle}</div>
    {/if}

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
                bind:value={vm.search_query}
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

        {#if !vm.is_loading && vm.search_query.trim().length > 0 && vm.search_results.length === 0}
            <p class="text-muted mb-0">No troopers matched your search.</p>
        {/if}

        {#if vm.search_results.length > 0}
            <div class="list-group">
                {#each vm.search_results as trooper (trooper.id)}
                    <button
                        type="button"
                        class="list-group-item list-group-item-action text-start"
                        onclick={() => (selected = vm.selectTrooper(trooper))}
                    >
                        <div class="fw-semibold">
                            {vm.getDisplayName(trooper)}
                        </div>
                        {#if vm.getSubtitle(trooper).length > 0}
                            <small class="text-muted"
                                >{vm.getSubtitle(trooper)}</small
                            >
                        {/if}
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
