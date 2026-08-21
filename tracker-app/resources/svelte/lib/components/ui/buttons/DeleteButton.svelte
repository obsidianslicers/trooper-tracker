<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        submitting?: boolean | null;
        click?: (() => void) | null;
    }
    let {
        href = null,
        click = null,
        label = "Delete",
        submitting = false,
    }: Props = $props();
</script>

<Button
    btnclass="btn-outline-danger"
    icon={submitting ? "fa-solid fa-spinner fa-spin" : "fa-trash"}
    disabled={submitting}
    {href}
    {click}
    {label}
/>
