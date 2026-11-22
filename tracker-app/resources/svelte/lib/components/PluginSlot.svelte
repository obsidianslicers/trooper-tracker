<!--
    Shows the markup plugins added to a slot. The server renders each slot's
    Blade views to HTML and shares them as the "plugin_slots" page prop. Slot
    names must be listed in App\Plugins\PluginSlots::SVELTE_SLOTS
    or SVELTE_PAGE_SLOTS. A page that renders its own slots passes `html`.

    A plugin's <form method="POST" data-inertia-form> is sent without leaving
    the page: its controller redirects back, and the page reloads its props.
-->
<script lang="ts">
    import { router, usePage } from "@inertiajs/svelte";

    let { name, html: given = null }: { name: string; html?: string | null } = $props();

    const page = usePage();

    const html = $derived(
        given ?? ((page.props.plugin_slots ?? {}) as Record<string, string>)[name] ?? "",
    );

    function submit(event: SubmitEvent) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.hasAttribute("data-inertia-form")) {
            return;
        }

        event.preventDefault();
        router.post(form.action, new FormData(form), { preserveScroll: true });
    }
</script>

{#if html}
    <div class="plugin-slot" data-plugin-slot={name} onsubmit={submit}>
        {@html html}
    </div>
{/if}
