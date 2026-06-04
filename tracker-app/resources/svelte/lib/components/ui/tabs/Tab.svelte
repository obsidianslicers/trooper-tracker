<script lang="ts">
    import { TABS_CONTEXT } from '$lib/constants';
    import { getContext, type Snippet } from 'svelte';

    interface Props {
        id: string;
        children: Snippet;
    }

    let { id, children }: Props = $props();

    const tabs = getContext(TABS_CONTEXT) as { current: string };
</script>

<li class="nav-item">
    <button
        onclick={() => (tabs.current = id)}
        class={['nav-link', { active: tabs.current === id }]}
    >
        {@render children()}
    </button>
</li>
