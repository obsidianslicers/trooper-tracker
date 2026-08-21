<script lang="ts">
    import InputError from "./InputError.svelte";
    interface Props {
        label?: string | null;
        value?: string;
        checked?: boolean;
        errors?: string | string[];
        disabled?: boolean;
        onchange?: (() => void) | null;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        label,
        value = "1",
        checked = $bindable(false),
        errors = [],
        disabled = false,
        onchange = null,
    }: Props = $props();
</script>

{#snippet checkbox()}
    <input
        type="checkbox"
        class={[
            "form-check-input",
            errors && errors.length > 0 ? "is-invalid" : "",
        ]}
        {value}
        {disabled}
        {id}
        {onchange}
        bind:checked
    />
{/snippet}

{#if label && label.length > 0}
    <div class="form-check">
        {@render checkbox()}
        <label class="form-check-label" for={id}>
            {label}
        </label>
    </div>
{:else}
    {@render checkbox()}
{/if}
<InputError {errors} />
