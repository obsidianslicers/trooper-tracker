import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type Trooper = {
    id: string | number;
    legal_name: string;
    display_name: string;
    email: string;
};

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
    mode: "admin" | null;
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
        mode: null,
        onSelect: null,
        ...options,
    };
}

export class TrooperPickerViewModel extends ViewModel {
    options: TrooperPickerOptions = $state(createTrooperPickerOptions());
    show_modal = $state(false);
    term = $state("");
    selected_trooper = $state<Trooper | null>(null);
    troopers = $state<Trooper[]>([]);
    is_loading = $state(false);
    error_message = $state("");
    request_sequence = $state(0);

    private debounce_timer: ReturnType<typeof setTimeout> | null = null;

    constructor(options: Partial<TrooperPickerOptions> = {}) {
        super();
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
        this.term = "";
        this.troopers = [];
        this.is_loading = false;
        this.error_message = "";
        this.request_sequence += 1;
        this.clearPendingSearch();
    }

    selectTrooper(trooper: Trooper): Trooper {
        this.selected_trooper = trooper;
        this.options.onSelect?.(trooper);
        this.closeModal();

        return trooper;
    }

    clearSelection(): null {
        this.selected_trooper = null;
        return null;
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

        if (this.troopers.length > 0) {
            this.troopers = [];
        }

        if (had_pending_timer || had_loading) {
            this.request_sequence += 1;
        }
    }

    scheduleSearch(query: string): void {
        this.clearPendingSearch();

        this.debounce_timer = setTimeout(() => {
            void this.runSearch(query);
        }, this.options.debounce_ms);
    }

    async runSearch(query: string): Promise<void> {
        if (query.trim().length < 3) {
            return;
        }

        const current_sequence = ++this.request_sequence;
        this.is_loading = true;
        this.error_message = "";

        try {
            const url = `${getRoute('search.troopers')}?search_term=${encodeURIComponent(query)}&search_mode=${this.options.mode ?? 'none'}`;
            const rows = await fetch(url).then(res => res.json());
            if (current_sequence !== this.request_sequence) {
                return;
            }

            this.troopers = rows;
        } catch (e) {
            if (current_sequence !== this.request_sequence) {
                return;
            }

            this.troopers = [];
            this.error_message = "Unable to search troopers right now.";
        } finally {
            if (current_sequence === this.request_sequence) {
                this.is_loading = false;
            }
        }
    }
}
