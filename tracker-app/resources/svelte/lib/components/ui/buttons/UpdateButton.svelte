<script lang="ts">
    import Button from './Button.svelte';

    interface Props {
        href?: string | null;
        label?: string | null;
        click?: (() => void) | null;
    }
    let { href = null, click = null, label = 'Update' }: Props = $props();
</script>

<Button btnclass="btn-outline-warning" icon="fa-edit" {href} {click} {label} />
