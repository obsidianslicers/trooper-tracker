import { ViewModel } from "$lib/domains/types.svelte";


export class DeleteViewModel extends ViewModel {
    show_modal: boolean = $state(false);

    constructor() {
        super();
    }
}