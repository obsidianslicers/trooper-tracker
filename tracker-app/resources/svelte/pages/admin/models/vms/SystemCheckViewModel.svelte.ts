import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export const ERRORS_PER_PAGE = 25;

/**
 * The System Check page's error list: which errors are expanded, and loading the next page of
 * older errors without running the checks again.
 */
export class SystemCheckViewModel {
    loading: boolean = $state(false);
    open: Record<number, boolean> = $state({});

    toggle = (index: number) => {
        this.open[index] = !this.open[index];
    };

    showMore = (shown: number) => {
        this.loading = true;

        router.get(getRoute("admin.system-check", { errors: shown + ERRORS_PER_PAGE }), {}, {
            only: ["log_entries", "has_more_errors"],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => (this.loading = false),
        });
    };
}
