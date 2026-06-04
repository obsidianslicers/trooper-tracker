<script lang="ts">
    import { usePage } from "@inertiajs/svelte";
    import PageTitle from "../lib/components/PageTitle.svelte";

    const page = usePage();

    const appName = $derived(
        (page.props.appName as string | undefined) ?? "Troop Tracker",
    );
</script>

<svelte:head>
    <title>{appName}</title>
</svelte:head>

<PageTitle title={appName} />

<section class="py-4">
    <h4 class="mb-3">Inertia + Svelte Is Ready</h4>
    <p class="mb-0">This page component is a starter for new Inertia routes.</p>
</section>
