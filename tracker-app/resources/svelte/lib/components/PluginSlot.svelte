<!--
    Shows the markup plugins added to a slot. The server renders each slot's
    Blade views to HTML and shares them as the "plugin_slots" page prop. Slot
    names must be listed in App\Plugins\PluginSlots::SVELTE_SLOTS
    or SVELTE_PAGE_SLOTS.
-->
<script lang="ts">
    import { usePage } from "@inertiajs/svelte";

    let { name }: { name: string } = $props();

    const page = usePage();

    const html = $derived(
        ((page.props.plugin_slots ?? {}) as Record<string, string>)[name] ?? "",
    );
</script>

{#if html}
    <div class="plugin-slot" data-plugin-slot={name}>
        {@html html}
    </div>
{/if}
