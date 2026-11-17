import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type {
    EventOrganizationLimit,
    EventOverview,
    EventOverviewForm,
    UpdateEventPageData,
} from "../types";

export type OverviewForm = EventOverviewForm & {
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

function toForm(overview: EventOverview): OverviewForm {
    const form = { ...overview.form };

    for (const field of NUMBER_FIELDS) {
        form[field] = text(form[field]);
    }

    return {
        ...form,
        organizations: overview.organizations.map((organization) => ({
            ...organization,
            troopers_allowed: text(organization.troopers_allowed),
            handlers_allowed: text(organization.handlers_allowed),
        })),
    };
}

/**
 * The Overview tab: the event's details, saved with the Update button because a
 * status change notifies troopers.
 */
export class OverviewViewModel extends SubmitableViewModel<OverviewViewModel, OverviewForm> {
    /** Holds the inputs plugins add to the form (their markup comes from the server). */
    plugin_fields: HTMLElement | null = $state(null);

    constructor(
        public readonly event_id: number,
        overview: EventOverview,
    ) {
        super();
        this.form = useForm<OverviewForm>(toForm(overview));
    }

    organizationError(organization_id: number, field: string): string | undefined {
        return (this.form.errors as Record<string, string>)[
            `organizations.${organization_id}.${field}`
        ];
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form
            .transform((data) => this.payload(data as OverviewForm) as unknown as OverviewForm)
            .post(
                getRoute("admin.events.update", { event: this.event_id }),
                createPartialReloadOptions({
                    onSuccess: (page) => this.sync(page.props.results as UpdateEventPageData),
                }),
            );
    };

    private payload(data: OverviewForm): Record<string, unknown> {
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

    private sync(results: UpdateEventPageData | undefined) {
        if (results?.overview) {
            this.form.defaults(toForm(results.overview)).reset();
        }
    }
}
