<!--
    The trooper's requests for troop credit and how each went. On their own record it also
    offers a way to ask for another.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import type { CreditRequest } from "../../models/record-types";

    let { requests, isSelf }: { requests: CreditRequest[]; isSelf: boolean } = $props();

    const status: Record<CreditRequest["status"], { label: string; classes: string }> = {
        awaiting_coordinator: { label: t("Waiting for the event coordinator"), classes: "text-bg-secondary" },
        awaiting_moderator: { label: t("Waiting for a moderator"), classes: "text-bg-secondary" },
        approved: { label: t("Approved"), classes: "text-bg-success" },
        denied: { label: t("Denied"), classes: "text-bg-danger" },
    };
</script>

{#if isSelf}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="text-muted small">{t("Trooped an event but never signed up for it? Ask for credit.")}</span>
        <a href={getRoute("events.troop-correction-form")} class="btn btn-sm btn-outline-primary ms-auto">
            {t("Request Troop Credit")}
        </a>
    </div>
{/if}

<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>{t("Troop")}</th>
                <th>{t("Status")}</th>
            </tr>
        </thead>
        <tbody>
            {#each requests as request (request.id)}
                <tr>
                    <td>
                        <a href={request.url}>{request.event_name}</a>
                        {#if request.troop_date}
                            <span class="d-block small text-muted">{request.troop_date}</span>
                        {/if}
                    </td>
                    <td>
                        <span class={["badge text-wrap", status[request.status].classes]}>{status[request.status].label}</span>
                        <span class="d-block small text-muted">
                            {t("Asked :date", { date: request.requested_on ?? "" })}
                        </span>
                        {#if request.decided_on}
                            <span class="d-block small text-muted">{t("Decided :date", { date: request.decided_on })}</span>
                        {/if}
                    </td>
                </tr>
            {:else}
                <tr>
                    <td colspan="2" class="text-center py-4">{t("No credit requests.")}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</div>
