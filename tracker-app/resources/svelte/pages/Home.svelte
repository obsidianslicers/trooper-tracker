<script lang="ts">
    import { usePage } from "@inertiajs/svelte";

    const page = usePage();

    const baseUrl = $derived(page.props.config.meta.base_url as string);
</script>

<div class="row">
    <div class="col text-center">
        <img
            src="{baseUrl}/img/logo.jpg"
            alt="Trooper Tracker Logo"
            class="img-fluid rounded"
        />
    </div>
</div>
