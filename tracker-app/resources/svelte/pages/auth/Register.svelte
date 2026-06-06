<script lang="ts">
    import PageTitle from "$lib/components/PageTitle.svelte";
    import { usePage } from "@inertiajs/svelte";

    const page = usePage();
</script>

<PageTitle title={appName} />

<section class="py-4">
    <h4 class="mb-3">Inertia + Svelte Is Ready</h4>
    <p class="mb-0">This page component is a starter for new Inertia routes.</p>
</section>
