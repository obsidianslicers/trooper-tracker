<script lang="ts">
    import { t } from "$lib/i18n";

    let { notice }: { notice: string | null } = $props();
</script>

{#if notice}
    <div class="same-day-notice d-flex align-items-baseline gap-2 mb-3" role="note">
        <i class="fa fa-fw fa-calendar-day flex-shrink-0"></i>
        <span>
            <strong>{notice}</strong>
            {t("Two shifts on the same day add one troop to your total, not two. Your hours and charity still count for every shift.")}
        </span>
    </div>
{/if}
