import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";


export class RequestDeletionViewModel extends ViewModel {
    show_modal: boolean = $state(false);
    confirm: string = $state("");
    submitting: boolean = $state(false);

    constructor() {
        super();
    }

    delete = (e: Event) => {
        e.preventDefault();
        this.submitting = true;
        const url = getRoute("account.request-deletion");

        const options = {
            onFinish: () => {
                this.submitting = false;
            }
        };

        router.post(url, {}, options);
    }
}