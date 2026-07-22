<script lang="ts">
    import type { Option } from "$lib/domains/types.svelte";
    import InputError from "./InputError.svelte";
    import InputLabel from "./InputLabel.svelte";
    interface Props {
        value?: unknown | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
        options: Option[];
        change?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        value = $bindable(),
        label = null,
        placeholder = null,
        options = [],
        errors = [],
        disabled = false,
        change = null,
    }: Props = $props();
</script>

<div class="form-floating">
    <select
        {id}
        class={[
            "form-select",
            errors.length > 0 ? "is-invalid border-danger" : "",
        ]}
        {disabled}
        bind:value
        onchange={change}
    >
        {#if placeholder && placeholder.length > 0}
            <option value={null} disabled>-- {placeholder} --</option>
        {/if}

        {#each options as option (option.value)}
            <option value={option.value}>{option.label}</option>
        {/each}
    </select>
    <InputLabel {id} {label} />
    <InputError {errors} />
</div>
