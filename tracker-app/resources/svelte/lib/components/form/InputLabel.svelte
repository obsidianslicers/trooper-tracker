<script lang="ts">
    interface Props {
        id: string;
        label?: string | null;
    }
    let { id, label = null }: Props = $props();
</script>

{#if label && label.length > 0}
    <label for={id}>
        {label}
    </label>
{/if}

<style>
    label {
        color: var(--bs-body-color);
    }
</style>
