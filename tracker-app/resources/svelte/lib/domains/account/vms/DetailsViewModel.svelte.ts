import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createDetailsForm(options: Partial<Details> = {}): InertiaForm<Details> {
    const data = {
        id: 0,
        email: '',
        displayName: '',
        legalName: '',
        membershipStatus: '',
        phone: '',
        theme: '',
        ...options
    };

    return useForm<Details>(data);
}

export type Details = {
    id: number;
    email: string;
    displayName: string;
    legalName: string;
    membershipStatus: string;
    phone: string;
    theme: string;
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