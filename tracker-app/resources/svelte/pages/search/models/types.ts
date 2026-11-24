/** SearchTrooperResource. */
export type SearchTrooper = {
    id: number;
    display_name: string;
    legal_name: string | null;
    url: string;
    identifiers: { club: string; label: string }[];
};

/** SearchEventResource. */
export type SearchEvent = {
    id: number;
    name: string;
    url: string;
    venue_address: string | null;
    organization: string | null;
    date: string | null;
    status: string;
};

/** SearchAllPageData. troopers / events are null when that kind wasn't searched. */
export type SearchResultsPageData = {
    term: string;
    type: string;
    searched: boolean;
    troopers: SearchTrooper[] | null;
    events: SearchEvent[] | null;
    limit: number;
    search_title: string;
    search_types: Record<string, string>;
};
