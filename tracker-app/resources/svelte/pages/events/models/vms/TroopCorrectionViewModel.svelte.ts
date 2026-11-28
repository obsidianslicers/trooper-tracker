import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

/**
 * The coordinator's answer to a request for troop credit: Confirm gives the credit, Deny hands
 * it to the moderators.
 */
export class TroopCorrectionViewModel {
    submitting: "confirm" | "deny" | null = $state(null);

    constructor(private readonly correction_id: number) {}

    confirm = () => this.answer("confirm", "events.confirm-troop-correction");

    deny = () => this.answer("deny", "events.deny-troop-correction");

    private answer(choice: "confirm" | "deny", route: string) {
        this.submitting = choice;

        router.post(getRoute(route, { troop_correction: this.correction_id }), {}, {
            preserveScroll: true,
            onFinish: () => {
                this.submitting = null;
            },
        });
    }
}
