import { ViewModel, type Option } from "$lib/domains/types.svelte";

/** Organization options come indented by level ("— — Squad"); this is the bare name. */
export function organizationName(option: Option): string {
    return option.label.replace(/^(—\s*)+/, "").trim();
}

export class OrganizationPickerViewModel extends ViewModel {
    show_modal: boolean = $state(false);
    term: string = $state("");

    constructor(private get_options: () => Option[]) {
        super();
    }

    /** All options while the search is empty, otherwise those whose name has the search text. */
    get filtered(): Option[] {
        const term = this.term.trim().toLowerCase();
        const options = this.get_options();

        if (term.length === 0) {
            return options;
        }

        return options.filter((option) => organizationName(option).toLowerCase().includes(term));
    }

    selectedLabel(value: unknown): string | null {
        const option = this.get_options().find((o) => o.value === value);

        return option ? organizationName(option) : null;
    }

    open = () => {
        this.term = "";
        this.show_modal = true;
    };

    close = () => {
        this.show_modal = false;
    };
}
