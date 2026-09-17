import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
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
    legal_name: string;
    display_name: string;
    email: string;
};

type MergeTroopersFormData = {
    source_trooper_id: string | number | null;
    target_trooper_id: string | number | null;
};


export class MergeTroopersViewModel extends SubmitableViewModel<MergeTroopersViewModel, MergeTroopersFormData> {
    #source_trooper = $state<Trooper | null | undefined>(null);
    #target_trooper = $state<Trooper | null | undefined>(null);

    constructor() {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createMergeTroopersForm();
    }

    get source_trooper(): Trooper | null | undefined {
        return this.#source_trooper;
    }

    set source_trooper(trooper: Trooper | null | undefined) {
        this.#source_trooper = trooper;
        this.form.source_trooper_id = trooper?.id ?? null;
    }

    get target_trooper(): Trooper | null | undefined {
        return this.#target_trooper;
    }

    set target_trooper(trooper: Trooper | null | undefined) {
        this.#target_trooper = trooper;
        this.form.target_trooper_id = trooper?.id ?? null;
    }

    submit = async (e: Event) => {
        e.preventDefault();

        const url = getRoute('admin.troopers.merge');

        this.form.post(url, {});
    };
}