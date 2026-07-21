<script lang="ts">
    import { TABS_CONTEXT } from '$lib/constants';
    import { onMount, setContext, type Snippet } from 'svelte';
    interface Props {
        children: Snippet;
        defaultTab?: string | null;
    }

    let { children, defaultTab }: Props = $props();

    // Create reactive state for the active tab ID
    let activeTab: string | null = $state(null);

    onMount(() => {
        // Initialize the active tab to the defaultTab if provided
        if (defaultTab) {
            activeTab = defaultTab;
        }
    });

    // Share the state with all children
    setContext(TABS_CONTEXT, {
        get current() {
            return activeTab;
        },
        set current(value) {
            activeTab = value;
        },
    });
</script>

<div class="tabs">
    {@render children()}
</div>
