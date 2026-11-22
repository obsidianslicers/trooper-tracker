<!-- "One troop per day" at the shift: a note when they're on another shift that day, or a warning before signing up for a second. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { DisplayShift } from "../../models";

    let { note }: { note: NonNullable<DisplayShift["same_day"]> } = $props();
</script>

{#if note.is_warning}
    <div class="same-day-notice same-day-notice--warning d-flex align-items-baseline gap-2 mt-3" role="note">
        <i class="fa fa-fw fa-triangle-exclamation flex-shrink-0"></i>
        <span>
            {t("You're already signed up for :times on :day.", { times: note.times, day: note.day })}
            {@html t("<strong>This shift won't add another troop:</strong> shifts on the same day count as one.")}
        </span>
    </div>
{:else}
    <div class="same-day-notice d-flex align-items-baseline gap-2 mt-3" role="note">
        <i class="fa fa-fw fa-calendar-day flex-shrink-0"></i>
        <span>
            {t("You're also signed up for :times on :day.", { times: note.times, day: note.day })}
            <strong>{t("Together these shifts count as one troop.")}</strong>
        </span>
    </div>
{/if}
