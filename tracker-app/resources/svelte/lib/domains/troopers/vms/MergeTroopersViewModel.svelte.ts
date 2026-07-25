import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { useForm, type InertiaForm } from "@inertiajs/svelte";

function createMergeTroopersForm(options: Partial<MergeTroopersFormData> = {},): InertiaForm<MergeTroopersFormData> {
    const data = {
        source_trooper_id: null,
        target_trooper_id: null,
        ...options,
    };

    return useForm<MergeTroopersFormData>(data);
}

export type Trooper = {
    id: string | number;
    [key: string]: unknown;
};

type MergeTroopersFormData = {
    source_trooper_id: string | number | null;
    target_trooper_id: string | number | null;
};


export class MergeTroopersViewModel extends SubmitableViewModel<MergeTroopersViewModel, MergeTroopersFormData> {
    #source_trooper = $state<Trooper | null>(null);
    #target_trooper = $state<Trooper | null>(null);

    constructor() {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createMergeTroopersForm();
    }

    get source_trooper(): Trooper | null {
        return this.#source_trooper;
    }

    set source_trooper(trooper: Trooper | null) {
        this.#source_trooper = trooper;
        this.form.target_trooper_id = trooper?.id ?? null;
    }

    get target_trooper(): Trooper | null {
        return this.#target_trooper;
    }

    set target_trooper(trooper: Trooper | null) {
        this.#target_trooper = trooper;
        this.form.source_trooper_id = trooper?.id ?? null;
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