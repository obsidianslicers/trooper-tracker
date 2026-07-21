import { useForm } from "@inertiajs/svelte";

export type Option = { value: string | number | object, label: string };

export abstract class ViewModel {
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