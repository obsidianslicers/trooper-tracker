<script lang="ts">
    import { t } from "$lib/i18n";
    import type { TroopToConfirm } from "../models";
    import type { AttendanceAnswer } from "../models/vms/PushNotificationsViewModel.svelte";

    let {
        troops,
        onanswer,
    }: {
        troops: TroopToConfirm[];
        onanswer: (troop: TroopToConfirm, status: AttendanceAnswer) => void;
    } = $props();
</script>

<div class="card mb-3 troops-to-confirm">
    <div class="card-header fw-semibold">{t("Did you make it?")}</div>
    <div class="list-group list-group-flush">
        {#each troops as troop (troop.id)}
            <div class="list-group-item py-3 troop-to-confirm-row">
                <a class="d-block text-break" href={troop.url}>{troop.event_name}</a>
                <div class="small opacity-75">{troop.shift_time}</div>
                <div class="troop-answers d-flex gap-2 mt-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success"
                        onclick={() => onanswer(troop, "attended")}
                    >
                        {t("Made It")}
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        onclick={() => onanswer(troop, "unabletoattend")}
                    >
                        {t("Missed It")}
                    </button>
                </div>
            </div>
        {/each}
    </div>
</div>
