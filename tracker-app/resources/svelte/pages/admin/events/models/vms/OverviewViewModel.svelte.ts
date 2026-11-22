import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { EventOverview, EventPageResults } from "../types";
import { EventFormViewModel, toEventForm, type EventForm } from "./EventFormViewModel.svelte";

export type OverviewForm = EventForm;

function toForm(overview: EventOverview): OverviewForm {
    return toEventForm(overview.form, overview.organizations);
}

/**
 * The Overview tab: the event's details, saved with the Update button because a
 * status change notifies troopers.
 */
export class OverviewViewModel extends EventFormViewModel<OverviewViewModel, OverviewForm> {
    constructor(
        public readonly event_id: number,
        overview: EventOverview,
    ) {
        super();
        this.form = useForm<OverviewForm>(toForm(overview));
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form
            .transform((data) => this.payload(data as OverviewForm) as unknown as OverviewForm)
            .post(
                getRoute("admin.events.update", { event: this.event_id }),
                createPartialReloadOptions({
                    onSuccess: (page) => this.sync(page.props.results as EventPageResults),
                }),
            );
    };

    private sync(results: EventPageResults | undefined) {
        if (results?.overview) {
            this.form.defaults(toForm(results.overview)).reset();
        }
    }
}
