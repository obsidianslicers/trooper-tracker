import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import { formatForPicker } from "../../../../../../js/custom/date-picker.js";
import { mandalorianMercsParser } from "../../../../../../js/admin/parsers/mandalorianMercsParser.js";
import { theLegionParser } from "../../../../../../js/admin/parsers/theLegionParser.js";
import type { NewEvent } from "../types";
import { EventFormViewModel, toEventForm, type EventForm } from "./EventFormViewModel.svelte";

export type CreateEventForm = EventForm & { source: string };

export type CreateMode = "email" | "manual";

type Parser = (message: string) => Record<string, unknown>;

/** Each club's request message has its own layout; the host's club picks the parser. */
const PARSERS: Record<string, Parser> = {
    "Mandalorian Mercs": mandalorianMercsParser,
    "501st Legion": theLegionParser,
};

const DATE_FIELDS = ["event_start", "event_end"];

/**
 * Create Event: paste the club's request message to fill the form in, or fill it in by hand.
 */
export class CreateEventViewModel extends EventFormViewModel<
    CreateEventViewModel,
    CreateEventForm
> {
    mode: CreateMode = $state("email");

    constructor(private readonly page: NewEvent) {
        super();
        this.form = useForm<CreateEventForm>({
            ...toEventForm(page.form, page.organizations),
            source: "",
        });
    }

    /** The club whose request message format the host uses, if any. */
    get club(): string | null {
        const host = this.page.host_options.find(
            (option) => option.value == this.form.organization_id,
        );

        return host?.club ?? null;
    }

    get hasHost(): boolean {
        return !!this.form.organization_id;
    }

    /** Fills the form in from the pasted message, then shows it to check. */
    parseSource = () => {
        const message = this.form.source ?? "";
        if (!message.trim() || !this.hasHost) {
            return;
        }

        const parser = this.club ? PARSERS[this.club] : undefined;
        const parsed = parser ? parser(message) : {};

        for (const [field, value] of Object.entries(parsed)) {
            if (value === null || value === undefined || field === "source") {
                continue;
            }
            (this.form as unknown as Record<string, unknown>)[field] = this.toField(field, value);
        }

        this.mode = "manual";
    };

    submit = (e: Event) => {
        e.preventDefault();

        this.form
            .transform((data) => this.payload(data as CreateEventForm) as unknown as CreateEventForm)
            .post(getRoute("admin.events.create"));
    };

    private toField(field: string, value: unknown): unknown {
        if (DATE_FIELDS.includes(field)) {
            // in the format this device's picker takes; a phone's own field drops others
            return formatForPicker(value, { withTime: true });
        }

        return typeof value === "number" ? String(value) : value;
    }
}
