<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        outline?: boolean | null;
        click?: (() => void) | null;
    }
    let {
        href = null,
        click = null,
        outline = true,
        label = "Create",
    }: Props = $props();
</script>

<Button
    btnclass={outline ? "btn-outline-success" : "btn-success"}
    icon="fa-plus"
    {href}
    {click}
    {label}
/>
