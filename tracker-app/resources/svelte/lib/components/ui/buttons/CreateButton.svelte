<script lang="ts">
    import Button from './Button.svelte';

    interface Props {
        href?: string | null;
        label?: string | null;
        click?: (() => void) | null;
    }
    let { href = null, click = null, label = 'Create' }: Props = $props();
</script>

<Button btnclass="btn-outline-success" icon="fa-plus" {href} {click} {label} />
