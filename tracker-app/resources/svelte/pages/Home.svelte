<script lang="ts">
    import PageTitle from "$lib/components/PageTitle.svelte";
    import { usePage } from "@inertiajs/svelte";

    const page = usePage();

    const appName = $derived(
        (page.props.config.branding.name as string | undefined) ??
            "Troop Tracker",
    );

    const baseUrl = $derived(page.props.config.meta.base_url as string);
</script>

<PageTitle title={appName} />

<div class="row">
    <div class="col text-center">
        <img
            src="{baseUrl}/img/logo.jpg"
            alt="Trooper Tracker Logo"
            class="img-fluid rounded"
        />
    </div>
</div>
