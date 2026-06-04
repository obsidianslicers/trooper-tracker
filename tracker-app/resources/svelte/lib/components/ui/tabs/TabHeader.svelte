<script lang="ts">
    let { children } = $props();
</script>

<div class="overflow-auto tabs-scroll-mobile">
    <ul class="nav nav-tabs flex-nowrap flex-md-wrap w-max-content w-md-auto">
        {@render children()}
    </ul>
</div>

<style>
    .w-max-content {
        width: max-content;
    }

    .tabs-scroll-mobile :global(.nav-tabs) {
        min-width: 100%;
    }

    .tabs-scroll-mobile :global(.nav-link) {
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .tabs-scroll-mobile {
            overflow-x: visible;
        }

        .w-md-auto {
            width: auto;
        }
    }
</style>
