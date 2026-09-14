import { ViewModel } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type Costume = {
    id: number;
    name: string;
    organizations: { id: number, name: string, selected: boolean }[];
};

export type TrooperCostume = {
    costume_id: number;
    name: string;
    costume_organizations: string;
    submitting: boolean;
};

export type CostumesPageData = TrooperCostume[];

export class CostumesViewModel extends ViewModel {
    trooper_costumes: TrooperCostume[] = $state([]);
    selected_costume: Costume | null = $state(null);
    submitting: boolean = $state(false);
    show_results: boolean = $state(false);
    search_term: string = $state("");
    searching: boolean = $state(false);
    search_results: Costume[] = $state([]);
    search_timeout: any = null;
    search_controller: AbortController | null = null;

    constructor(pageData: CostumesPageData) {
        super();
        this.trooper_costumes = pageData || [];
    }

    searchCostumes = () => {
        this.show_results = true;
        clearTimeout(this.search_timeout);
        // Abort any active fetch request from a previous search
        if (this.search_controller) {
            this.search_controller.abort();
        }

        if (this.search_term && this.search_term.trim().length > 0) {
            this.search_timeout = setTimeout(this.performSearch, 300);
        }
    }

    selectCostume = (costume: Costume) => {
        this.selected_costume = costume;
        this.selected_costume.organizations.forEach((org) => {
            org.selected = false;
        });
        this.search_term = costume.name;
        this.show_results = false;
    }

    removeCostume = (trooper_costume: TrooperCostume) => {
        trooper_costume.submitting = true;

        const url = getRoute('account.remove-costume');

        //  fire & forget the request, but we want to preserve the current URL and state
        const options = createPartialReloadOptions({
            onSuccess: (page: any) => {
                toastStateSvelte.success(`${trooper_costume.name} removed successfully.`);
                trooper_costume.submitting = false;
                this.trooper_costumes = page.props.results.trooper_costumes ?? [];
            }
        });

        const data = {
            costume_id: trooper_costume.costume_id,
        };

        router.post(url, data, options);
    }

    addCostume = () => {
        this.submitting = true;

        const url = getRoute('account.add-costume');

        //  fire & forget the request, but we want to preserve the current URL and state
        const options = createPartialReloadOptions({
            onSuccess: (page: any) => {
                toastStateSvelte.success(`${this.selected_costume?.name} added successfully.`);
                this.selected_costume = null;
                this.submitting = false;
                this.trooper_costumes = page.props.results.trooper_costumes ?? [];
            }
        });

        const organization_ids = this.selected_costume?.organizations.filter((org) => org.selected).map((org) => org.id);

        const data = {
            costume_id: this.selected_costume?.id,
            organization_ids: organization_ids,
        };

        router.post(url, data, options);
    }

    canAddCostume = (): boolean => {
        if (!this.selected_costume) {
            return false;
        }
        return this.selected_costume.organizations.some((org) => org.selected);
    }

    hideSearchResults = () => {
        setTimeout(() => (this.show_results = false), 150);
    }

    showSearchResults = () => {
        this.show_results = true;
    }

    private performSearch = () => {
        this.searching = true;
        this.search_controller = new AbortController();
        this.search_results = [];
        const url = `${getRoute("search.costumes")}?search_term=${encodeURIComponent(this.search_term)}`;
        fetch(url, { signal: this.search_controller.signal })
            .then((res) => res.json())
            .then((data) => {
                this.search_results = data;
                this.searching = false;
            })
            .catch((error) => {
                // Ignore abort errors caused by typing new characters
                if (error.name !== "AbortError") {
                    console.error("Search aborted:", error);
                }
            })
            .finally(() => {
                this.searching = false;
            });
    }
}