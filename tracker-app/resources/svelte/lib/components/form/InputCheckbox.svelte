<script lang="ts">
    import InputError from './InputError.svelte';
    interface Props {
        label?: string | null;
        value?: string;
        checked?: boolean;
        errors?: string[];
        disabled?: boolean;
    }

    const id = 'id-' + crypto.randomUUID();

    let {
        label,
        value = '1',
        checked = $bindable(false),
        errors = [],
        disabled = false,
    }: Props = $props();
</script>

<div class="form-check">
    <input
        type="checkbox"
        class={['form-check-input', errors.length > 0 ? 'is-invalid' : '']}
        {value}
        {disabled}
        {id}
        bind:checked
    />
    {#if label && label.length > 0}
        <label class="form-check-label" for={id}>
            {label}
        </label>
    {/if}
</div>
<InputError {errors} />
