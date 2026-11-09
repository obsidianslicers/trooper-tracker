import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { IEventTypeForm } from "../types";

export type UpdatePageData = {
    event_type: IEventTypeForm & {
        id: number;
        events_count: number;
        trooper_stamps: ITrooperStamps;
    };
};

function generateForm(event_type: UpdatePageData["event_type"]): InertiaForm<IEventTypeForm> {
    return useForm<IEventTypeForm>({
        name: event_type.name,
        description: event_type.description ?? "",
        is_counted: event_type.is_counted,
        is_active: event_type.is_active,
    });
}

export class UpdateViewModel
    extends SubmitableViewModel<UpdateViewModel, IEventTypeForm>
    implements ISubmitableViewModel<IEventTypeForm> {

    event_type_id: number = $state(0);
    events_count: number = $state(0);
    was_counted: boolean = $state(true);
    trooper_stamps: ITrooperStamps | null = $state(null);

    constructor(pageData: UpdatePageData) {
        super();

        this.event_type_id = pageData.event_type.id;
        this.events_count = pageData.event_type.events_count;
        this.was_counted = pageData.event_type.is_counted;
        this.trooper_stamps = pageData.event_type.trooper_stamps;
        this.form = generateForm(pageData.event_type);
    }

    /** Changing whether a type counts recalculates every past event of that type. */
    get changes_past_totals(): boolean {
        return this.events_count > 0 && this.form.is_counted !== this.was_counted;
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.event-types.update", { event_type: this.event_type_id }));
    };
}
