<script lang="ts">
    import InputError from "./InputError.svelte";
    interface Props {
        label?: string | null;
        value?: string;
        checked?: boolean;
        errors?: string | string[];
        disabled?: boolean;
        change?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        label,
        value = "1",
        checked = $bindable(false),
        errors = [],
        disabled = false,
        change = null,
    }: Props = $props();
</script>

<div class="form-check">
    <input
        type="checkbox"
        class={[
            "form-check-input",
            errors && errors.length > 0 ? "is-invalid" : "",
        ]}
        {value}
        {disabled}
        {id}
        bind:checked
        onchange={change}
    />
    {#if label && label.length > 0}
        <label class="form-check-label" for={id}>
            {label}
        </label>
    {/if}
</div>
<InputError {errors} />
