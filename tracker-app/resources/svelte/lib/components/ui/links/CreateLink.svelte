<script lang="ts">
    import Link from "./Link.svelte";
    interface Props {
        label?: string | null;
        href: string;
    }

    let { label = "", href }: Props = $props();
</script>

<Link {label} {href} cssclass="btn-outline-success" icon="fa-add" />
