<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        href?: string | null;
        label?: string | null;
        submitting?: boolean | null;
        outline?: boolean | null;
        small?: boolean | null;
        disabled?: boolean | null;
        icon?: string | null;
        click?: (() => void) | null;
    }
    let {
        href = null,
        click = null,
        outline = true,
        small = false,
        submitting = false,
        disabled = false,
        icon = "fa-trash",
        label = "Delete",
    }: Props = $props();

    let button_classes = $derived(() => {
        let classes = outline ? "btn-outline-danger" : "btn-danger";
        if (small) {
            classes += " btn-sm";
        }
        return classes;
    });
</script>

<Button
    btnclass={button_classes()}
    icon={submitting ? "fa-solid fa-spinner fa-spin" : icon}
    disabled={submitting || disabled}
    {href}
    {click}
    {label}
/>
