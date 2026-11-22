<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { TroopsToConfirmViewModel, type TroopsToConfirmPageData } from "./models";

    const page = usePage<TroopsToConfirmPageData>();

    pageState.title = t("Troops to confirm");

    const vm = new TroopsToConfirmViewModel();

    const troops = $derived(page.props.troops);
</script>

<SlimView wide>
    {#if troops.length > 0}
        <p class="text-muted">
            {t("Let us know whether you made it to each troop, so it counts on your service record.")}
        </p>

        <ul class="list-group">
            {#each troops as troop (troop.id)}
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <div class="min-w-0">
                        <a href={troop.event_url} class="fw-semibold text-break">{troop.event_name}</a>
                        <div class="small text-muted">
                            {troop.shift_label}{#if troop.is_tentative}&nbsp;· {t("Tentative")}{/if}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-success"
                            disabled={vm.submitting_id !== null}
                            onclick={() => vm.answer(troop, "attended")}
                        >
                            {t("Made It")}
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger"
                            disabled={vm.submitting_id !== null}
                            onclick={() => vm.answer(troop, "unabletoattend")}
                        >
                            {t("Missed It")}
                        </button>
                    </div>
                </li>
            {/each}
        </ul>
    {:else}
        <p class="text-muted text-center py-4">
            {t("You're all caught up. Every troop you signed up for has an answer.")}
        </p>
    {/if}
</SlimView>
