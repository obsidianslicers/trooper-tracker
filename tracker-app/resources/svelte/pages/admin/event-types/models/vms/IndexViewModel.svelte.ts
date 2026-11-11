import { ViewModel } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type EventTypeRow = {
    id: number;
    name: string;
    description: string | null;
    is_counted: boolean;
    is_active: boolean;
    events_count: number;
    can_delete: boolean;
    update_route?: string;
};

export type IndexPageData = {
    event_types: EventTypeRow[];
};

export class IndexViewModel extends ViewModel {
    event_types: EventTypeRow[] = $state([]);
    delete_event_type: EventTypeRow | null = $state(null);
    deleting: boolean = $state(false);

    constructor(pageData: IndexPageData) {
        super();

        this.event_types = pageData.event_types.map((t) => ({
            ...t,
            update_route: getRoute("admin.event-types.update", { event_type: t.id }),
        }));
    }

    get show_delete(): boolean { return this.delete_event_type !== null; }

    reorder = (ordered_ids: number[]) => {
        const url = getRoute("admin.event-types.reorder");

        const options = createPartialReloadOptions({
            onSuccess: () => toastStateSvelte.success("Event types reordered."),
        });

        router.post(url, { ids: ordered_ids }, options);
    };

    cancelDelete = () => {
        this.delete_event_type = null;
    };

    deleteEventType = (e: Event) => {
        e.preventDefault();

        if (!this.delete_event_type) {
            return;
        }

        this.deleting = true;

        const id = this.delete_event_type.id;
        const url = getRoute("admin.event-types.delete", { event_type: id });

        const options = createPartialReloadOptions({
            onFinish: () => {
                this.deleting = false;
                this.delete_event_type = null;
                this.event_types = this.event_types.filter((t) => t.id !== id);
            },
        });

        router.post(url, {}, options);
    };
}
