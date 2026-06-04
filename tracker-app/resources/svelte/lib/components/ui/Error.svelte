<script lang="ts">
    import { Exception } from '$lib/exceptions';
    import Alert from './Alert.svelte';
    import SlimCard from './SlimCard.svelte';

    interface Props {
        exception: Exception | null;
    }

    let { exception }: Props = $props();
</script>

{#if exception instanceof Exception}
    <SlimCard>
        {#snippet header()}
            Unexpected Error
        {/snippet}
        <Alert type="danger">
            {exception.message}
        </Alert>
    </SlimCard>
{/if}
