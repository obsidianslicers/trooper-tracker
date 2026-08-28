<script lang="ts">
    import type { Snippet } from "svelte";

    interface Props {
        type?:
            | "info"
            | "success"
            | "warning"
            | "danger"
            | "primary"
            | "secondary"
            | "light"
            | "dark";
        icon?: string;
        classes?: string;
        children?: Snippet;
    }

    let {
        type = "primary",
        icon = "fa-exclamation-circle",
        classes = "",
        children,
    }: Props = $props();
</script>

<div class="alert alert-{type} {classes.trim()} mb-3">
    <i class="fa fa-fw {icon.trim()}"></i>
    {@render children?.()}
</div>
