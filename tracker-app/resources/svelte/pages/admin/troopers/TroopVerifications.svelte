<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import LinkedText from "$lib/components/LinkedText.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import type { TroopVerificationsPageData } from "./models/review-types";
    import { ReviewQueueViewModel } from "./models/vms/ReviewQueueViewModel.svelte";

    const page = usePage<TroopVerificationsPageData>();

    pageState.title = t("Troop Verifications");

    const vm = new ReviewQueueViewModel();
</script>

<SlimView wide={true}>
    <p class="text-muted">
        {t("These troopers need a moderator to confirm their troops. Approve each club that should get credit. A club you approve counts the troop right away.")}
    </p>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>{t("Trooper")}</th>
                    <th>{t("Troop")}</th>
                    <th>{t("Costume")}</th>
                    <th>{t("Club credit")}</th>
                </tr>
            </thead>
            <tbody>
                {#each page.props.event_troopers as row (row.id)}
                    <tr>
                        <td>
                            <a href={getRoute("admin.troopers.show", { trooper: row.trooper.id, tab: "events" })}>
                                {row.trooper.name}
                            </a>
                        </td>
                        <td>
                            {#if row.event}
                                <a href={getRoute("events.display", { event: row.event.id })}>{row.event.name}</a>
                            {/if}
                            <br />
                            <span class="text-muted small">{row.starts_at}</span>
                            {#if row.correction}
                                <div class="correction small mt-2">
                                    <strong>{t("Didn't sign up. Their details:")}</strong>
                                    <span class="correction-details text-break"><LinkedText text={row.correction.details} /></span>
                                    {#if row.correction.coordinator_denied}
                                        <br />
                                        <span class="text-danger">{t("The event coordinator said they weren't there.")}</span>
                                    {/if}
                                </div>
                            {/if}
                        </td>
                        <td class={[!row.has_costume && "text-muted"]}>{row.costume}</td>
                        <td class="text-nowrap">
                            {#each row.clubs as club (club.id)}
                                {@const busy = vm.isWorking(`credit-${row.id}-${club.id}`)}
                                <div class="mb-1">
                                    <span class="me-2">{club.name}</span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success"
                                        disabled={busy}
                                        onclick={() => vm.approveCredit(row.id, club.id)}
                                    >
                                        {t("Approve")}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        disabled={busy}
                                        onclick={() => vm.removeCredit(row.id, club.id)}
                                    >
                                        {t("Remove")}
                                    </button>
                                </div>
                            {/each}
                        </td>
                    </tr>
                {:else}
                    <tr>
                        <td colspan="4" class="text-muted">{t("No troops are waiting for verification.")}</td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</SlimView>

<style>
    /* keeps the Troop column readable on phones; the table scrolls sideways instead */
    .correction {
        min-width: 14rem;
    }

    .correction-details {
        white-space: pre-line;
    }
</style>
