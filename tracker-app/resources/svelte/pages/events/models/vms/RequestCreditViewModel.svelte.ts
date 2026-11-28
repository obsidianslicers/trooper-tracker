import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { CorrectionEvent, RequestCreditForm, RequestCreditPageData } from "../correction-types";

/**
 * Request Troop Credit: find the completed event by date or title, pick it, then send the
 * shift, costume, clubs and details. Picking an event reloads the page's event and costume
 * options; the search keeps its place.
 */
export class RequestCreditViewModel extends SubmitableViewModel<RequestCreditViewModel, RequestCreditForm> {
    date: string | null = $state(null);
    search: string = $state("");

    constructor(private readonly props: () => RequestCreditPageData) {
        super();

        const page = props();
        this.date = page.date;
        this.search = page.search ?? "";
        this.form = useForm<RequestCreditForm>(this.formFor(page.event));
    }

    get event(): CorrectionEvent | null {
        return this.props().event;
    }

    /** Clubs the picked costume (or no costume) can credit. */
    get clubs(): { id: number; name: string }[] {
        const options = this.props().credit_options;

        if (!options) {
            return [];
        }

        const ids = this.clubIdsFor(this.form.costume_id);

        return options.clubs.filter((club) => ids.includes(club.id));
    }

    lookup = (e: Event) => {
        e.preventDefault();
        this.visit({});
    };

    pick = (event_id: number) => {
        this.visit({ event_id }, () => {
            const event = this.props().event;
            Object.assign(this.form, this.formFor(event));
        });
    };

    clearEvent = () => {
        this.visit({}, () => Object.assign(this.form, this.formFor(null)));
    };

    costumeChanged = () => {
        this.form.organization_ids = this.clubIdsFor(this.form.costume_id);
    };

    toggleClub = (club_id: number, checked: boolean) => {
        const others = this.form.organization_ids.filter((id) => id !== club_id);
        this.form.organization_ids = checked ? [...others, club_id] : others;
    };

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(getRoute("events.request-troop-correction"), { preserveScroll: true });
    };

    private visit(extra: Record<string, string | number>, done: (() => void) | null = null) {
        const query: Record<string, string | number> = { ...extra };

        if (this.date) {
            query.date = this.date;
        }

        if (this.search.trim() !== "") {
            query.search = this.search.trim();
        }

        router.get(getRoute("events.troop-correction-form"), query, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => done?.(),
        });
    }

    private clubIdsFor(costume_id: number | null): number[] {
        const options = this.props().credit_options;

        if (!options) {
            return [];
        }

        if (costume_id === null) {
            return options.no_costume_club_ids;
        }

        return options.costumes.find((costume) => costume.id === costume_id)?.club_ids ?? [];
    }

    private formFor(event: CorrectionEvent | null): RequestCreditForm {
        return {
            event_id: event?.id ?? null,
            event_shift_id: event?.shifts[0]?.id ?? null,
            costume_id: null,
            organization_ids: event ? this.clubIdsFor(null) : [],
            details: "",
            proof_url: "",
        };
    }
}
