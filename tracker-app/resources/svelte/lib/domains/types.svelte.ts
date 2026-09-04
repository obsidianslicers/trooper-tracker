import { useForm } from "@inertiajs/svelte";

export type Option = { value: string | number | boolean | object, label: string };

export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

export abstract class ViewModel {
}

export interface ITrooperStamps {
    created_id: number;
    created_at: string;
    created_by: string;
    updated_id: number;
    updated_at: string;
    updated_by: string;
    deleted_id: number;
    deleted_at: string;
    deleted_by: string;
}

export interface ISubmitableViewModel<TForm extends Record<string, any> = any> {
    form: ReturnType<typeof useForm<TForm>>;
    submitting: boolean;
    dirty: boolean;
    errors: Record<string, string[]>;
    submit: (e: Event) => void;
}

export abstract class SubmitableViewModel<T, TForm extends Record<string, any> = any> extends ViewModel {
    public form = null as unknown as ReturnType<typeof useForm<TForm>>;

    get submitting(): boolean {
        return this.form?.processing ?? false;
    }

    get dirty(): boolean {
        return this.form?.isDirty ?? false;
    }

    get errors(): Record<string, string[]> {
        return this.form?.errors ?? {};
    }
}