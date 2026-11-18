import { createPartialReloadOptions } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import { ViewModel } from "./types.svelte";

export type SaveState = "idle" | "saving" | "saved" | "error";

type AutosaveForm<TForm extends Record<string, any>> = ReturnType<typeof useForm<TForm>>;

/**
 * A form that saves itself as it changes, with no Save button. Each save posts the
 * whole form; a change made while a save is running is saved right after it.
 *
 * Saves run as async visits, so several rows saving at once don't cancel each other.
 * The command answers with the page's fresh data as `results`, which synced() gets.
 */
export abstract class AutosaveViewModel<TForm extends Record<string, any>, TResults = unknown>
    extends ViewModel {
    save_state: SaveState = $state("idle");
    public form: AutosaveForm<TForm>;

    private queued = false;

    constructor(fields: TForm) {
        super();
        this.form = useForm<TForm>(fields);
    }

    protected abstract url(): string;

    /** The request body for the form's fields; the fields themselves by default. */
    protected payload(data: TForm): Record<string, unknown> {
        return data;
    }

    /** Called with the fresh page data after each successful save. */
    protected synced(_results: TResults | undefined): void {}

    get errors(): Record<string, string> {
        return (this.form.errors ?? {}) as Record<string, string>;
    }

    save = () => {
        if (this.form.processing) {
            this.queued = true;
            return;
        }

        this.save_state = "saving";

        this.form
            .transform((data) => this.payload(data as TForm) as unknown as TForm)
            .post(
                this.url(),
                createPartialReloadOptions({
                    async: true,
                    onSuccess: (page) => {
                        this.save_state = "saved";
                        this.form.clearErrors();
                        this.synced(page.props.results as TResults | undefined);
                    },
                    onError: () => {
                        this.save_state = "error";
                    },
                    onFinish: () => this.saveQueued(),
                }),
            );
    };

    private saveQueued() {
        if (this.queued) {
            this.queued = false;
            this.save();
        }
    }
}
