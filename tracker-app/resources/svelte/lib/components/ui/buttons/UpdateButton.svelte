<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        submitting?: boolean | null;
        outline?: boolean | null;
        disabled?: boolean | null;
        icon?: string | null;
        click?: (() => void) | null;
    }
    let {
        href = null,
        click = null,
        outline = true,
        submitting = false,
        disabled = false,
        icon = "fa-edit",
        label = "Update",
    }: Props = $props();
</script>

<Button
    btnclass={outline ? "btn-outline-warning" : "btn-warning"}
    icon={submitting ? "fa-solid fa-spinner fa-spin" : icon}
    disabled={submitting || disabled}
    {href}
    {click}
    {label}
/>
