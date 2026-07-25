import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createMergeTroopersForm(options: Partial<MergeTroopers> = {}): InertiaForm<MergeTroopers> {
    const data = {
        source_trooper: null,
        target_trooper: null,
        ...options
    };

    return useForm<MergeTroopers>(data);
}

type Trooper = {
    id: string | number;
    [key: string]: unknown;
};

type MergeTroopers = {
    source_trooper: Trooper | null;
    target_trooper: Trooper | null;
};


export class MergeTroopersViewModel extends SubmitableViewModel<MergeTroopersViewModel, MergeTroopers> {

    constructor() {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createMergeTroopersForm();
    }

    submit = async (e: Event) => {
        e.preventDefault();

        // const url = getRoute('auth.login');

        // const toast = toastStateSvelte.info('Logging in...', { delay: 4000 });

        // this.form.post(url, {
        //     preserveScroll: true,
        //     onError: () => {
        //         // Dismiss the loading toast if validation fails on backend
        //         toastStateSvelte.dismiss(toast);
        //     }
        // });
    };
}