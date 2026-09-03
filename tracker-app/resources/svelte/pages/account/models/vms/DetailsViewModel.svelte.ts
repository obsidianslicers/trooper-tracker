import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute, propertyRemover } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function generateForm(options: Partial<DetailsForm> = {}): InertiaForm<DetailsForm> {
    const data = {
        id: 0,
        display_name: '',
        legal_name: '',
        display_costume_id: null,
        phone: '',
        theme: '',
        ...options
    };

    propertyRemover(data, ['display_costumes', 'theme_enums']);

    return useForm<DetailsForm>(data);
}

export type DetailsForm = {
    display_name: string;
    legal_name: string;
    phone: string;
    display_costume_id: number | null;
    theme: string;
};

export type DetailsPageData = DetailsForm & {
    display_costumes: Option[];
    theme_enums: Option[];
}

export class DetailsViewModel extends SubmitableViewModel<DetailsViewModel, DetailsForm> {
    display_costumes: Option[] = $state([]);
    theme_enums: Option[] = $state([]);

    constructor(pageData?: DetailsPageData) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = generateForm(pageData);
        this.display_costumes = pageData?.display_costumes || [];
        this.theme_enums = pageData?.theme_enums || [];
    }

    submit = async (e: Event) => {
        e.preventDefault();

        const url = getRoute('account.update-profile');

        const options =
        {
            // preserveScroll: true, // Prevents page from jumping
            preserveUrl: true,     // Keeps the current URL intact
            preserveState: true,  // Keeps current local form/scroll states intact
            only: ['flash', 'results'],

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