<script lang="ts">
    interface Props {
        label?: string | null;
        href: string;
        icon: string;
        cssclass: string;
    }

    let {
        label = "",
        href = "#",
        icon = "fa-question",
        cssclass = "",
    }: Props = $props();
</script>

<a {href} class={["btn btn-sm", cssclass]}>
    <i class={["fa fa-fw", icon, "me-1"]}></i>
    {label}
</a>
