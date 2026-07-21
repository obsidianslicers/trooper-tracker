<script lang="ts">
    import Button from "./Button.svelte";

    interface Props {
        label?: string | null;
        submitting?: boolean | null;
        disabled?: boolean | null;
        click?: (() => void) | null;
    }

    let {
        label = "Submit",
        submitting = false,
        disabled = false,
        click = null,
    }: Props = $props();
</script>

<Button
    btnclass="btn-primary"
    icon={submitting ? "fa-solid fa-spinner fa-spin" : null}
    label={submitting ? "Transmitting ..." : label}
    disabled={submitting || disabled}
    submit={true}
    type="submit"
    {click}
/>
