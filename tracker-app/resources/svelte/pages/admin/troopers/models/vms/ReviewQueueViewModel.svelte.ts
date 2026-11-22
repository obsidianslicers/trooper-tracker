import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

/**
 * A queue of things to approve or turn down. Each decision is sent on its own, and the page
 * reloads its list when it lands, so a decided row drops out.
 */
export class ReviewQueueViewModel extends ViewModel {
    /** The row being decided, so its buttons wait. */
    working: string | null = $state(null);

    decide = (key: string, route: string, params: Record<string, number>) => {
        this.working = key;

        router.post(getRoute(route, params), {}, {
            preserveScroll: true,
            onFinish: () => {
                this.working = null;
            },
        });
    };

    approveCostume = (trooper_costume_id: number) => {
        this.decide(`costume-${trooper_costume_id}`, "admin.troopers.costumes.approve", {
            trooper_costume: trooper_costume_id,
        });
    };

    denyCostume = (trooper_costume_id: number) => {
        this.decide(`costume-${trooper_costume_id}`, "admin.troopers.costumes.deny", {
            trooper_costume: trooper_costume_id,
        });
    };

    approveCredit = (event_trooper_id: number, organization_id: number) => {
        this.decide(`credit-${event_trooper_id}-${organization_id}`, "admin.troopers.troop-verifications.approve", {
            event_trooper: event_trooper_id,
            organization: organization_id,
        });
    };

    removeCredit = (event_trooper_id: number, organization_id: number) => {
        this.decide(`credit-${event_trooper_id}-${organization_id}`, "admin.troopers.troop-verifications.reject", {
            event_trooper: event_trooper_id,
            organization: organization_id,
        });
    };

    isWorking(key: string): boolean {
        return this.working === key;
    }
}
