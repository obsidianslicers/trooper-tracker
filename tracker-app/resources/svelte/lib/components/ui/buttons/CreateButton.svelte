<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        submitting?: boolean | null;
        outline?: boolean | null;
        small?: boolean | null;
        disabled?: boolean | null;
        click?: (() => void) | null;
    }
    let {
        href = null,
        label = "Create",
        submitting = false,
        outline = true,
        small = false,
        disabled = false,
        click = null,
    }: Props = $props();

    let buttonClass = $derived(() => {
        let classes = outline ? "btn-outline-success" : "btn-success";
        if (small) {
            classes += " btn-sm";
        }
        return classes;
    });
</script>

<Button
    btnclass={buttonClass()}
    icon={submitting ? "fa-solid fa-spinner fa-spin" : "fa-plus"}
    disabled={submitting || disabled}
    {href}
    {click}
    {label}
/>
