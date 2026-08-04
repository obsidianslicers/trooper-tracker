import { ViewModel } from "$lib/domains/types.svelte";
import type { Details } from "./DetailsViewModel.svelte";

export type AccountPageData = {
    details: Details;
};

export class AccountViewModel extends ViewModel {

    constructor(pageData?: AccountPageData) {
        super();
    }
}