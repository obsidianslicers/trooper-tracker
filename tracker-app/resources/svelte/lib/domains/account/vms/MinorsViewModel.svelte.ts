import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";

export type Minor = {
    id: number;
    legal_name: string;
    display_name: string;
};

export type MinorsPageData = Minor[];

export class MinorsViewModel extends ViewModel {
    minors: Minor[] = $state([]);

    constructor(pageData?: MinorsPageData) {
        super();
        this.minors = pageData?.length ? pageData : [];
    }

    getServiceRecordUrl = (minor: Minor): string => {
        return getRoute('service-records.trooper', { trooper: minor.id });
    }
}