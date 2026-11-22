import { router } from "@inertiajs/svelte";

type FilterValue = string | number | boolean | number[] | null;

/**
 * The filters of a report that runs with GET: holds what's picked and reloads the page with it.
 */
export class ReportFiltersViewModel<T extends Record<string, FilterValue>> {
    filters: T = $state() as T;

    constructor(filters: T, private readonly url: string) {
        this.filters = { ...filters };
    }

    /** The query string for the picked filters, leaving out the empty ones. */
    query(): Record<string, string | number | number[]> {
        const query: Record<string, string | number | number[]> = {};

        for (const [key, value] of Object.entries(this.filters)) {
            if (value === null || value === "" || value === false) {
                continue;
            }

            query[key] = value === true ? 1 : value;
        }

        return query;
    }

    run = (event?: Event): void => {
        event?.preventDefault();
        router.get(this.url, this.query());
    };
}
