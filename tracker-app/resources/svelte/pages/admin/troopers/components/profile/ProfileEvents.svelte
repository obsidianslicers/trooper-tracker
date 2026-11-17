<script lang="ts">
    import { t } from "$lib/i18n";
    import type { ProfileEvent } from "../../models";

    interface Props {
        events: ProfileEvent[];
    }
    let { events }: Props = $props();
</script>

<table class="table table-sm">
    <thead>
        <tr><th>{t("Organization")}</th><th>{t("Last Event")}</th><th>{t("Date")}</th></tr>
    </thead>
    <tbody>
        {#each events as event (event.organization)}
            <tr class={{ "table-warning": event.is_old }}>
                <td>{event.organization}</td>
                <td>
                    {#if event.event_id}
                        <a href={`/events/${event.event_id}`}>{event.event_name}</a>
                    {:else}
                        <span class="text-muted">{t("No events")}</span>
                    {/if}
                </td>
                <td>{event.shift_date ?? ""}</td>
            </tr>
        {/each}
    </tbody>
</table>
