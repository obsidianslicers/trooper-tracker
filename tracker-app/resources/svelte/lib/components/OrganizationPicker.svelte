<script lang="ts">
    import { t } from "$lib/i18n";
    import InputError from "$lib/components/form/InputError.svelte";
    import {
        OrganizationPickerViewModel,
        organizationName,
    } from "$lib/components/models/OrganizationPickerViewModel.svelte";
    import Modal from "$lib/components/ui/Modal.svelte";
    import type { Option } from "$lib/domains/types.svelte";

    interface Props {
        /** The organizations to pick from, indented by level (`GetModeratedOrganizationOptions`). */
        options: Option[];
        value?: unknown;
        label?: string;
        placeholder?: string;
        modal_title?: string;
        errors?: string | string[];
        disabled?: boolean;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        options,
        value = $bindable(null),
        label = t("Organization"),
        placeholder = t("Select an Organization"),
        modal_title = t("Select an Organization"),
        errors = [],
        disabled = false,
    }: Props = $props();

    const vm = new OrganizationPickerViewModel(() => options);

    const has_errors = $derived(Array.isArray(errors) ? errors.length > 0 : !!errors);

    function pick(option: Option) {
        value = option.value;
        vm.close();
    }
</script>

<div class="form-floating">
    <input
        {id}
        type="text"
        class={["form-control pointer", has_errors && "is-invalid"]}
        value={vm.selectedLabel(value) ?? placeholder}
        readonly
        {disabled}
        onclick={vm.open}
        onkeydown={(event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                vm.open();
            }
        }}
    />
    <label for={id}>{label}</label>
    <InputError {errors} />
</div>

<Modal bind:show={vm.show_modal} title={modal_title} canClose={vm.close}>
    <div class="mb-3">
        <label class="form-label" for="{id}-search">{t("Search")}</label>
        <input
            id="{id}-search"
            type="text"
            class="form-control"
            autocomplete="off"
            bind:value={vm.term}
        />
    </div>

    {#if vm.filtered.length === 0}
        <p class="text-muted mb-0">{t("No organizations matched your search.")}</p>
    {:else}
        <div class="list-group organization-picker-list">
            {#each vm.filtered as option (option.value)}
                <button
                    type="button"
                    class={["list-group-item list-group-item-action text-start", option.value === value && "active"]}
                    onclick={() => pick(option)}
                >
                    {vm.term.trim().length > 0 ? organizationName(option) : option.label}
                </button>
            {/each}
        </div>
    {/if}
</Modal>

<style>
    .organization-picker-list {
        max-height: 60vh;
        overflow-y: auto;
    }
</style>
