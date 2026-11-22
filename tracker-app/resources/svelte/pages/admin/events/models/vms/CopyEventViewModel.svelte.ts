import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { CopyPageData } from "../list-types";

type CopyForm = { name: string; event_start: string | null };

export class CopyEventViewModel
    extends SubmitableViewModel<CopyEventViewModel, CopyForm>
    implements ISubmitableViewModel<CopyForm> {

    event: CopyPageData["event"];

    constructor(pageData: CopyPageData) {
        super();

        this.event = pageData.event;
        this.form = useForm<CopyForm>({ name: pageData.event.name, event_start: pageData.event.event_start });
    }

    get cancel_url(): string {
        return getRoute("admin.events.edit", { event: this.event.id });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.events.copy.store", { event: this.event.id }));
    };
}
