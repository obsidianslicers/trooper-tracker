<script lang="ts">
    import { t } from "$lib/i18n";
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
            {t("Unexpected Error")}
        {/snippet}
        <Alert type="danger">
            {exception.message}
        </Alert>
    </SlimCard>
{/if}
