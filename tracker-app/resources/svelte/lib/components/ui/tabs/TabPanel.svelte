<script lang="ts">
    import { TABS_CONTEXT } from '$lib/constants';
    import { getContext, type Snippet } from 'svelte';

    interface Props {
        id: string;
        children: Snippet;
    }

    let { id, children }: Props = $props();
    const tabs = getContext<{ current: string | null }>(TABS_CONTEXT);
</script>

{#if tabs && tabs.current === id}
    <div class="tab-pane active">
        {@render children()}
    </div>
{/if}
