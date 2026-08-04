import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createDetailsForm(options: Partial<Details> = {}): InertiaForm<Details> {
    const data = {
        id: 0,
        display_name: '',
        legal_name: '',
        display_costume_id: null,
        display_costumes: [],
        phone: '',
        theme: '',
        themes: [],
        ...options
    };

    return useForm<Details>(data);
}

export type Details = {
    id: number;
    display_name: string;
    legal_name: string;
    phone: string;
    display_costume_id: number | null;
    display_costumes: Option[];
    theme: string;
    themes: Option[];
};

export class DetailsViewModel extends SubmitableViewModel<DetailsViewModel, Details> {

    constructor(pageData?: Details) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createDetailsForm(pageData);
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