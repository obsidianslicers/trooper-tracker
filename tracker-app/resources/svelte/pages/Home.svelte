<script lang="ts">
    import { t } from "$lib/i18n";
    import { usePage } from "@inertiajs/svelte";

    const page = usePage();

    const baseUrl = $derived(page.props.config.meta.base_url as string);
</script>

<div class="row">
    <div class="col text-center">
        <img
            src="{baseUrl}/img/logo.jpg"
            alt={t("Trooper Tracker Logo")}
            class="img-fluid rounded"
        />
    </div>
</div>
