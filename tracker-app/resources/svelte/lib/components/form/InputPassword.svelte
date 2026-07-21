<script lang="ts">
    import InputText from "./InputText.svelte";

    interface Props {
        value?: string | null;
        label?: string | null;
        placeholder?: string | null;
        errors?: string | string[];
        disabled?: boolean;
    }

    let {
        value = $bindable(),
        label = null,
        placeholder = null,
        errors = [],
        disabled = false,
    }: Props = $props();
</script>

<InputText
    type="password"
    {label}
    {errors}
    {placeholder}
    {disabled}
    bind:value
/>
