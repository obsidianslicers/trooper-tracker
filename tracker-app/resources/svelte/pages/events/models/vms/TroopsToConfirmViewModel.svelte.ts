import { router } from "@inertiajs/svelte";
import type { TroopToConfirm } from "../types";

export type AttendanceAnswer = "attended" | "unabletoattend";

/**
 * The "Troops to confirm" page: each answer posts to the troop's confirm_url, and the server
 * sends the page back without that troop. The troops themselves come from page props, so
 * they stay current after each answer.
 */
export class TroopsToConfirmViewModel {
    //  the troop whose answer is on its way, so its buttons can't be pressed twice
    submitting_id: number | null = $state(null);

    answer = (troop: TroopToConfirm, status: AttendanceAnswer) => {
        if (this.submitting_id !== null) {
            return;
        }

        this.submitting_id = troop.id;

        router.post(troop.confirm_url, { status }, {
            preserveScroll: true,
            onFinish: () => {
                this.submitting_id = null;
            },
        });
    };
}
