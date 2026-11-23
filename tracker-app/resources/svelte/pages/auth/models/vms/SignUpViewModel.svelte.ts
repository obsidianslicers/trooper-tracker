import { ViewModel } from "$lib/domains/types.svelte";
import type { SignUpOptions } from "../types";

export type SignUpPageData = {
    oauth: SignUpOptions;
};

export class SignUpViewModel extends ViewModel {
    oauth: SignUpOptions | null = $state(null);

    constructor(pageData?: SignUpPageData) {
        super();
        if (pageData) {
            this.oauth = pageData.oauth;
        }
    }
}