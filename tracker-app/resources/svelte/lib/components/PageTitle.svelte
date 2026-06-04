<script lang="ts">
    interface Props {
        title: string;
    }
    let { title }: Props = $props();
</script>

<div>
    <h1 class="text-center py-2">
        {title}
    </h1>
</div>
