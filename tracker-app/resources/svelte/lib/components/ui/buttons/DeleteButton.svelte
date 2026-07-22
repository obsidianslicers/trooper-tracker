<script lang="ts">
    import Button from './Button.svelte';

    interface Props {
        href?: string | null;
        label?: string | null;
        click?: (() => void) | null;
    }
    let { href = null, click = null, label = 'Delete' }: Props = $props();
</script>

<Button btnclass="btn-outline-danger" icon="fa-trash" {href} {click} {label} />
