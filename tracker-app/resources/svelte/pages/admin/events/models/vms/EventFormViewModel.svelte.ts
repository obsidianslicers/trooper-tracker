import { SubmitableViewModel } from "$lib/domains/types.svelte";
import type { EventOrganizationLimit, EventOverviewForm } from "../types";

export type EventForm = EventOverviewForm & {
    organizations: EventOrganizationLimit[];
};

const NUMBER_FIELDS = [
    "latitude",
    "longitude",
    "expected_attendees",
    "requested_number_characters",
    "shifts_allowed",
    "troopers_allowed",
    "handlers_allowed",
    "friends_allowed",
    "guests_allowed",
] as const;

/** Numbers arrive as numbers; the text inputs hold strings ("" means none). */
function text(value: unknown): string {
    return value === null || value === undefined ? "" : String(value);
}

export function toEventForm(
    fields: EventOverviewForm,
    organizations: EventOrganizationLimit[],
): EventForm {
    const form = { ...fields };

    for (const field of NUMBER_FIELDS) {
        form[field] = text(form[field]);
    }

    return {
        ...form,
        coordinator_trooper_id: form.coordinator_trooper_id ?? "",
        organizations: organizations.map((organization) => ({
            ...organization,
            troopers_allowed: text(organization.troopers_allowed),
            handlers_allowed: text(organization.handlers_allowed),
        })),
    };
}

/** The event details form shared by Create Event and the event's Overview tab. */
export abstract class EventFormViewModel<
    T,
    TForm extends EventForm = EventForm,
> extends SubmitableViewModel<T, TForm> {
    /** Holds the inputs plugins add to the form (their markup comes from the server). */
    plugin_fields: HTMLElement | null = $state(null);

    organizationError(organization_id: number, field: string): string | undefined {
        return (this.form.errors as Record<string, string>)[
            `organizations.${organization_id}.${field}`
        ];
    }

    protected payload(data: TForm): Record<string, unknown> {
        const { organizations, ...fields } = data;

        return {
            ...fields,
            organizations: Object.fromEntries(
                organizations.map(({ id, can_attend, troopers_allowed, handlers_allowed }) => [
                    id,
                    { can_attend, troopers_allowed, handlers_allowed },
                ]),
            ),
            ...this.pluginValues(),
        };
    }

    private pluginValues(): Record<string, string> {
        const values: Record<string, string> = {};
        const inputs = this.plugin_fields?.querySelectorAll<HTMLInputElement>("[name]") ?? [];

        inputs.forEach((input) => {
            if ((input.type === "checkbox" || input.type === "radio") && !input.checked) {
                return;
            }
            values[input.name] = input.value;
        });

        return values;
    }
}
