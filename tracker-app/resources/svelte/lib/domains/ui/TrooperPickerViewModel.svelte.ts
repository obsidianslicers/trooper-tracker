import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

export type Trooper = {
    id: string | number;
    [key: string]: unknown;
};

type TrooperPicker = {
};

type TrooperSearch = (query: string) => Promise<Trooper[]>;

function createTrooperPickerForm(
    options: Partial<TrooperPicker> = {},
): InertiaForm<TrooperPicker> {
    const data = {
        ...options,
    };

    return useForm<TrooperPicker>(data);
}

export interface TrooperPickerOptions {
    label: string | null;
    placeholder: string;
    button_label: string;
    modal_title: string;
    search_placeholder: string;
    debounce_ms: number;
    disabled: boolean;
    errors: string | string[];
    clearable: boolean;
    onSelect: ((trooper: Trooper) => void) | null;
}

function createTrooperPickerOptions(
    options: Partial<TrooperPickerOptions> = {},
): TrooperPickerOptions {
    return {
        label: null,
        placeholder: "",
        button_label: "Pick Trooper",
        modal_title: "Select Trooper",
        search_placeholder: "Search troopers by name, TK ID, or email",
        debounce_ms: 350,
        disabled: false,
        errors: [],
        clearable: true,
        onSelect: null,
        ...options,
    };
}

export class TrooperPickerViewModel extends SubmitableViewModel<
    TrooperPickerViewModel,
    TrooperPicker
> {
    options: TrooperPickerOptions = $state(createTrooperPickerOptions());
    show_modal = $state(false);
    search_query = $state("");
    search_results = $state<Trooper[]>([]);
    is_loading = $state(false);
    error_message = $state("");
    request_sequence = $state(0);

    private debounce_timer: ReturnType<typeof setTimeout> | null = null;

    constructor(options: Partial<TrooperPickerOptions> = {}) {
        super();
        this.form = createTrooperPickerForm();
        this.setOptions(options);
    }

    setOptions(options: Partial<TrooperPickerOptions> = {}): void {
        this.options = createTrooperPickerOptions(options);
    }

    openModal(): void {
        this.show_modal = true;
        this.error_message = "";
    }

    closeModal(): void {
        this.show_modal = false;
        this.search_query = "";
        this.search_results = [];
        this.is_loading = false;
        this.error_message = "";
        this.request_sequence += 1;
        this.clearPendingSearch();
    }

    selectTrooper(trooper: Trooper): Trooper {
        this.options.onSelect?.(trooper);
        this.closeModal();

        return trooper;
    }

    clearSelection(): null {
        return null;
    }

    getDisplayName(trooper: Trooper): string {
        const by_display_name = trooper.display_name;
        if (typeof by_display_name === "string" && by_display_name.length > 0) {
            return by_display_name;
        }

        const by_name = trooper.name;
        if (typeof by_name === "string" && by_name.length > 0) {
            return by_name;
        }

        const first_name = trooper.first_name;
        const last_name = trooper.last_name;
        if (typeof first_name === "string" || typeof last_name === "string") {
            return `${String(first_name ?? "")} ${String(last_name ?? "")}`.trim();
        }

        return `Trooper #${String(trooper.id)}`;
    }

    getSubtitle(trooper: Trooper): string {
        const tk_id = trooper.tk_id;
        if (typeof tk_id === "string" && tk_id.length > 0) {
            return tk_id;
        }

        const email = trooper.email;
        if (typeof email === "string" && email.length > 0) {
            return email;
        }

        return "";
    }

    clearPendingSearch(): void {
        if (this.debounce_timer) {
            clearTimeout(this.debounce_timer);
            this.debounce_timer = null;
        }
    }

    resetSearchForEmptyQuery(): void {
        const had_pending_timer = this.debounce_timer !== null;
        const had_loading = this.is_loading;

        this.clearPendingSearch();

        if (this.is_loading) {
            this.is_loading = false;
        }

        if (this.error_message.length > 0) {
            this.error_message = "";
        }

        if (this.search_results.length > 0) {
            this.search_results = [];
        }

        if (had_pending_timer || had_loading) {
            this.request_sequence += 1;
        }
    }

    scheduleSearch(query: string, search_troopers: TrooperSearch): void {
        this.clearPendingSearch();

        this.debounce_timer = setTimeout(() => {
            void this.runSearch(query, search_troopers);
        }, this.options.debounce_ms);
    }

    async runSearch(query: string, search_troopers: TrooperSearch): Promise<void> {
        const current_sequence = ++this.request_sequence;
        this.is_loading = true;
        this.error_message = "";

        try {
            const rows = await search_troopers(query);
            if (current_sequence !== this.request_sequence) {
                return;
            }

            this.search_results = rows;
        } catch {
            if (current_sequence !== this.request_sequence) {
                return;
            }

            this.search_results = [];
            this.error_message = "Unable to search troopers right now.";
        } finally {
            if (current_sequence === this.request_sequence) {
                this.is_loading = false;
            }
        }
    }

    submit = async (e: Event) => {
        e.preventDefault();

        // const url = getRoute('auth.login');

        // const toast = toastStateSvelte.info('Logging in...', { delay: 4000 });

        // this.form.post(url, {
        //     preserveScroll: true,
        //     onError: () => {
        //         // Dismiss the loading toast if validation fails on backend
        //         toastStateSvelte.dismiss(toast);
        //     }
        // });
    };
}
