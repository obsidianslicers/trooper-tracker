import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type PickerCostume = {
    id: number;
    name: string;
    organizations: { id: number; name: string }[];
};

export interface CostumePickerOptions {
    label: string | null;
    placeholder: string;
    modal_title: string;
    debounce_ms: number;
    disabled: boolean;
    errors: string | string[];
    onSelected: ((costume: PickerCostume | null) => void) | null;
}

function createCostumePickerOptions(
    options: Partial<CostumePickerOptions> = {},
): CostumePickerOptions {
    return {
        label: null,
        placeholder: "No costume selected",
        modal_title: "Select Costume",
        debounce_ms: 350,
        disabled: false,
        errors: [],
        onSelected: null,
        ...options,
    };
}

export class CostumePickerViewModel extends ViewModel {
    #options: CostumePickerOptions;
    show_modal = $state(false);
    term = $state("");
    costumes = $state<PickerCostume[]>([]);
    is_loading = $state(false);
    error_message = $state("");

    private request_sequence = 0;
    private debounce_timer: ReturnType<typeof setTimeout> | null = null;

    constructor(get_options: () => Partial<CostumePickerOptions> = () => ({})) {
        super();
        this.#options = $derived(createCostumePickerOptions(get_options()));
    }

    get options(): CostumePickerOptions {
        return this.#options;
    }

    openModal(): void {
        this.show_modal = true;
        this.error_message = "";
    }

    closeModal(): void {
        this.show_modal = false;
        this.term = "";
        this.costumes = [];
        this.is_loading = false;
        this.error_message = "";
        this.request_sequence += 1;
        this.clearPendingSearch();
    }

    selectCostume(costume: PickerCostume): PickerCostume {
        this.closeModal();
        this.options.onSelected?.(costume);

        return costume;
    }

    clearSelection(): null {
        this.options.onSelected?.(null);

        return null;
    }

    clearPendingSearch(): void {
        if (this.debounce_timer) {
            clearTimeout(this.debounce_timer);
            this.debounce_timer = null;
        }
    }

    scheduleSearch(query: string): void {
        this.clearPendingSearch();

        if (query.length < 2) {
            this.request_sequence += 1;
            this.costumes = [];
            this.is_loading = false;
            return;
        }

        this.debounce_timer = setTimeout(() => {
            void this.runSearch(query);
        }, this.options.debounce_ms);
    }

    async runSearch(query: string): Promise<void> {
        const current_sequence = ++this.request_sequence;
        this.is_loading = true;
        this.error_message = "";

        try {
            const url = `${getRoute("search.costumes")}?search_term=${encodeURIComponent(query)}`;
            const rows = await fetch(url).then((res) => res.json());

            if (current_sequence === this.request_sequence) {
                this.costumes = rows;
            }
        } catch {
            if (current_sequence === this.request_sequence) {
                this.costumes = [];
                this.error_message = "Unable to search costumes right now.";
            }
        } finally {
            if (current_sequence === this.request_sequence) {
                this.is_loading = false;
            }
        }
    }
}
