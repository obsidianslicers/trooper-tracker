import { ViewModel } from "$lib/domains/types.svelte";
import type { AuthConfiguration } from "../types";

export type SignUpPageData = {
    oauth: AuthConfiguration;
};

export class SignUpViewModel extends ViewModel {
    oauth: AuthConfiguration | null = $state(null);

    constructor(pageData?: SignUpPageData) {
        super();
        if (pageData) {
            this.oauth = pageData.oauth;
        }
    }
}