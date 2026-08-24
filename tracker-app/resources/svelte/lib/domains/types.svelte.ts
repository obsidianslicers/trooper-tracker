import { router, useForm } from "@inertiajs/svelte";

export type Option = { value: string | number | boolean | object, label: string };

export abstract class ViewModel {
}

export abstract class DeletableListViewModel<T extends { id: number }> extends ViewModel {
    deleting: T | null = $state(null);
    submitting: boolean = $state(false);

    protected abstract deleteRoute(item: T): string;

    get show(): boolean {
        return this.deleting !== null;
    }

    set show(value: boolean) {
        if (!value) {
            this.deleting = null;
        }
    }

    confirmDelete = (item: T) => {
        this.deleting = item;
    };

    cancelDelete = () => {
        this.deleting = null;
    };

    delete = (e: Event) => {
        e.preventDefault();

        if (!this.deleting) {
            return;
        }

        this.submitting = true;

        router.post(
            this.deleteRoute(this.deleting),
            {},
            {
                onFinish: () => {
                    this.submitting = false;
                    this.deleting = null;
                },
            },
        );
    };
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