import { ValidationException } from '$lib/exceptions';

export type Option = { value: string | number | object, label: string };

export type Brand<K, T> = T & { __brand: K };

export abstract class ViewModel<T> {
    abstract load(): Promise<T>;
}

export abstract class SubmitableViewModel<T> extends ViewModel<T> {
    submitting = $state(false);
    errors: Record<string, string[]> = $state({});

    validationErrorHandler = (err: unknown) => {
        if (err instanceof ValidationException) {
            this.errors = err.errors;
        }
    };
}

export abstract class RevertibleViewModel<T, TState>
    extends SubmitableViewModel<T> {
    #original = $state<unknown>(null);
    dirty = $derived(this.#isDirty());

    protected abstract get source(): TState;
    protected abstract set source(value: TState);
    protected get original(): TState { return this.#original as TState; };
    protected set original(value: TState) { this.#original = $state.snapshot(value); };

    #isDirty(): boolean {
        if (this.#original === null) {
            return false;
        }

        return JSON.stringify(this.source) !== JSON.stringify(this.#original);
    }

    revert = async (): Promise<void> => {
        if (this.#original === null) {
            return;
        }

        this.source = $state.snapshot(this.#original) as TState;
        this.errors = {};
    };
}