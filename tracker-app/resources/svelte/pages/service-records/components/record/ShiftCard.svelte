<!--
    One shift as a card: the event, when, and a footer (the costume) the caller fills in.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { Snippet } from "svelte";

    interface Props {
        shift: { event_name: string; event_url: string; date: string; time: string; logo_url: string };
        badges?: Snippet;
        footer: Snippet;
    }
    let { shift, badges, footer }: Props = $props();
</script>

<div class="col-12 col-md-6 col-lg-4">
    <div class="card h-100">
        <div class="card-header card-shift-header">
            <div class="d-flex gap-2 align-items-start">
                <img src={shift.logo_url} alt={t("Logo")} width="32" height="32" />
                <h6 class="card-title mb-0 flex-grow-1">
                    <a href={shift.event_url}>{shift.event_name}</a>
                    {@render badges?.()}
                </h6>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <small class="text-muted d-block">{t("Date & Time")}</small>
                <p class="mb-0">{shift.date}</p>
                <p class="text-muted small mb-0">{shift.time}</p>
            </div>
        </div>
        <div class="card-footer">
            {@render footer()}
        </div>
    </div>
</div>
