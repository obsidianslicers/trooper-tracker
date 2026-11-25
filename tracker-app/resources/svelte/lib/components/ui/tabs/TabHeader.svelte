<script lang="ts">
    import ScrollHint from '../ScrollHint.svelte';

    let { children } = $props();
</script>

<ScrollHint class="tabs-scroll-mobile">
    <ul class="nav nav-tabs flex-nowrap flex-md-wrap w-max-content w-md-auto">
        {@render children()}
    </ul>
</ScrollHint>

<style>
    .w-max-content {
        width: max-content;
    }

    :global(.tabs-scroll-mobile) .nav-tabs {
        min-width: 100%;
    }

    :global(.tabs-scroll-mobile) :global(.nav-link) {
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        /* Wide screens wrap the tabs instead, and must not clip the active tab's border. */
        :global(.scroll-hint > .scroll-hint-row.tabs-scroll-mobile) {
            overflow-x: visible;
        }

        .w-md-auto {
            width: auto;
        }
    }
</style>
