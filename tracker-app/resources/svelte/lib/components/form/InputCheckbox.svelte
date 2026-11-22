<script lang="ts">
    import type { Snippet } from "svelte";
    import InputError from "./InputError.svelte";
    interface Props {
        label?: string | null;
        /** The field's name, for forms that submit the browser's way. */
        name?: string | null;
        value?: string;
        checked?: boolean;
        errors?: string | string[];
        disabled?: boolean;
        onchange?: (() => void) | null;
        /** Rich label content; used instead of `label` when provided. */
        children?: Snippet;
    }

    const id = "id-" + crypto.randomUUID();

    let {
        label,
        name = null,
        value = "1",
        checked = $bindable(false),
        errors = [],
        disabled = false,
        onchange = null,
        children,
    }: Props = $props();
</script>

{#snippet checkbox()}
    <input
        type="checkbox"
        class={[
            "form-check-input",
            errors && errors.length > 0 ? "is-invalid" : "",
        ]}
        {name}
        {value}
        {disabled}
        {id}
        {onchange}
        bind:checked
    />
{/snippet}

{#if children || (label && label.length > 0)}
    <div class="form-check">
        {@render checkbox()}
        <label class="form-check-label" for={id}>
            {#if children}
                {@render children()}
            {:else}
                {label}
            {/if}
        </label>
    </div>
{:else}
    {@render checkbox()}
{/if}
<InputError {errors} />
