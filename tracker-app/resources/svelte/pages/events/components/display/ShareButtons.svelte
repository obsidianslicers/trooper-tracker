<!-- Add to calendar, the plugins' share buttons, Watch, and sharing on social sites. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import type { CalendarLinks, ShareLink } from "../../models";
    import ShareLinks from "./ShareLinks.svelte";
    import WatchToggle from "./WatchToggle.svelte";

    interface Props {
        calendar: CalendarLinks;
        shares: ShareLink[];
        share_buttons_html: string;
        is_watching: boolean;
        watch_url: string;
    }

    let { calendar, shares, share_buttons_html, is_watching, watch_url }: Props = $props();
</script>

<div class="row">
    <div class="col-12 col-lg-6 mb-2 mb-lg-0">
        <div class="btn-group w-100" role="group" aria-label={t("Add event to calendar")}>
            <a href={calendar.google} class="btn btn-outline-secondary flex-fill" target="_blank"
               rel="noopener noreferrer" title={t("Add to Google Calendar")}>
                <i class="fab fa-google"></i>
            </a>
            <a href={calendar.yahoo} class="btn btn-outline-secondary flex-fill" target="_blank"
               rel="noopener noreferrer" title={t("Add to Yahoo Calendar")}>
                <i class="fab fa-yahoo"></i>
            </a>
            <a href={calendar.outlook} class="btn btn-outline-secondary flex-fill" target="_blank"
               rel="noopener noreferrer" title={t("Add to Outlook Calendar")}>
                <i class="fab fa-microsoft"></i>
            </a>
            <a href={calendar.ics} class="btn btn-outline-secondary flex-fill" title={t("Add to Calendar (.ics)")}>
                <i class="fa fa-fw fa-calendar"></i>
            </a>
        </div>
    </div>
    <div class="col-12 col-lg-6 mb-2 mb-lg-0 d-flex d-lg-block align-items-center justify-content-between gap-2">
        <div class="btn-group" style="overflow-x: auto;">
            <PluginSlot name="events.display.share-buttons" html={share_buttons_html} />
            <WatchToggle {is_watching} url={watch_url} />
        </div>

        <div class="mt-0 mt-lg-2 mb-0 mb-lg-3 flex-shrink-0">
            <ShareLinks links={shares} />
        </div>
    </div>
</div>
