<script lang="ts">
    import { t } from "$lib/i18n";

    let { name }: { name: string } = $props();
</script>

<div class="bg-dark border border-warning rounded p-4 mb-4 text-center">
    <p class="text-warning fst-italic fs-5 mb-2">{t('"The Force will be with you, always."')}</p>
    <p class="text-light mb-2">
        {t(
            ":name has become one with the Force. Their service, dedication, and the light they brought to those they served will be honored here, always.",
            { name },
        )}
    </p>
    <p class="text-warning small mb-0 fst-italic">
        {t("— In memoriam. May the Force be with them, always. —")}
    </p>
</div>
