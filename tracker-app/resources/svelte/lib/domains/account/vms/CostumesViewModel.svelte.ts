import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type Costume = {
    id: number;
    name: string;
    organizations: { id: number, name: string }[];
};

export type TrooperCostume = {
    id: number;
    name: string;
    costume_organizations: string;
};

export type CostumesPageData = {
    trooper_costumes: TrooperCostume[];
};

export class CostumesViewModel extends ViewModel {
    trooper_costumes: TrooperCostume[] = $state([]);
    show_results: boolean = $state(false);
    search_term: string = $state("");
    searching: boolean = $state(false);
    search_results: Costume[] = $state([]);
    search_timeout: any = null;
    search_controller: AbortController | null = null;

    constructor(pageData?: CostumesPageData) {
        super();
        this.trooper_costumes = pageData?.trooper_costumes?.length ? pageData.trooper_costumes : [];
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

    // function selectCostume(costume) {
    //     selectedCostume = costume;
    //     search = costume.name;
    //     this.show_results = false;
    //     form.organization_costume_ids = [];
    // }

    // async function handleSubmit(e: Event) {
    //     e.preventDefault();
    //     toastStateSvelte.info("Submitting costume selection...");
    // }

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