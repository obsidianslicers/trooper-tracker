<script lang="ts">
    interface Props {
        label: string;
        classes?: string | null;
    }
    let { label, classes = null }: Props = $props();
</script>

<span class={["badge", classes]}>
    {label}
</span>
