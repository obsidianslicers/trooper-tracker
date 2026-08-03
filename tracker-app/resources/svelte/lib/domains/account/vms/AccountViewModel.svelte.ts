import { ViewModel } from "$lib/domains/types.svelte";

export type AccountPageData = {
};

export class AccountViewModel extends ViewModel {

    constructor(pageData?: AccountPageData) {
        super();
        // // Initialize Inertia's useForm hook directly inside the instance
        // this.form = createLoginForm();
        // if (pageData) {
        //     this.oauth = pageData.oauth;
        //     this.form.defaults();
        // }
    }
}