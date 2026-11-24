<!-- Pills beside a past shift that added no troop: a non-credit event, or a same-day repeat. -->
<script lang="ts">
    import type { RecentShift } from "../../models/record-types";

    let { shift, cls = "" }: { shift: RecentShift; cls?: string } = $props();
</script>

{#if shift.not_counted}
    <span class="badge rounded-pill not-counted-pill {cls}" title={shift.not_counted.hint}>
        <i class="fa fa-fw fa-circle-info"></i>
        {shift.not_counted.label}
    </span>
{/if}
{#if shift.same_day_repeat}
    <span class="badge rounded-pill not-counted-pill {cls}" title={shift.same_day_repeat.hint}>
        <i class="fa fa-fw fa-calendar-day"></i>
        {shift.same_day_repeat.label}
    </span>
{/if}
