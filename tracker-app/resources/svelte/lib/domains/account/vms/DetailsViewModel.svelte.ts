import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createDetailsForm(options: Partial<Details> = {}): InertiaForm<Details> {
    const data = {
        id: 0,
        display_name: '',
        legal_name: '',
        display_costume_id: null,
        phone: '',
        theme: '',
        ...options
    };

    return useForm<Details>(data);
}

export type DetailsPageData = {
    id: number;
    display_name: string;
    legal_name: string;
    phone: string;
    display_costume_id: number | null;
    display_costumes: Option[];
    theme: string;
    theme_enums: Option[];
};

export type Details = {
    display_name: string;
    legal_name: string;
    phone: string;
    display_costume_id: number | null;
    theme: string;
};

export class DetailsViewModel extends SubmitableViewModel<DetailsViewModel, Details> {
    display_costumes: Option[] = $state([]);
    theme_enums: Option[] = $state([]);

    constructor(pageData?: DetailsPageData) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createDetailsForm(pageData);
        this.display_costumes = pageData?.display_costumes || [];
        this.theme_enums = pageData?.theme_enums || [];
    }

    submit = async (e: Event) => {
        e.preventDefault();

        const url = getRoute('account.update-profile');

        const options =
        {
            preserveUrl: true,     // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            preserveScroll: true, // Prevents page from jumping
            only: ['results'],

            onSuccess: (page: any) => {
                // Access the direct data return value mapped to page props
                const results = page.props.results;

                this.form.defaults();

                if (results) {
                }
            }
        };

        this.form.post(url, options);
    };
}