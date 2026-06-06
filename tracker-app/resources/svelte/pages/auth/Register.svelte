<script lang="ts">
    import PageTitle from "$lib/components/PageTitle.svelte";
    import { usePage } from "@inertiajs/svelte";

    const page = usePage();
</script>

<PageTitle title={appName} />

<SlimView></SlimView>
