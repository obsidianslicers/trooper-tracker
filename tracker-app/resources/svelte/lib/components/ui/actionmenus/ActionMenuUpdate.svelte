<script lang="ts">
    import ActionMenuLink from "./ActionMenuLink.svelte";
    interface Props {
        href?: string | null;
        click?: (() => void) | null;
    }

    let { href = null, click = null }: Props = $props();
</script>

<ActionMenuLink label="Update" icon="fa-pencil" {href} {click} />
