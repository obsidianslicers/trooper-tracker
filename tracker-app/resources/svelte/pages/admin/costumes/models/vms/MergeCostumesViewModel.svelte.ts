import type { PickerCostume } from "$lib/components/models/CostumePickerViewModel.svelte";
import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";

type MergeCostumesFormData = {
    source_costume_id: number | null;
    target_costume_id: number | null;
};

export class MergeCostumesViewModel extends SubmitableViewModel<
    MergeCostumesViewModel,
    MergeCostumesFormData
> {
    #source_costume = $state<PickerCostume | null>(null);
    #target_costume = $state<PickerCostume | null>(null);

    constructor() {
        super();
        this.form = useForm<MergeCostumesFormData>({
            source_costume_id: null,
            target_costume_id: null,
        });
    }

    get source_costume(): PickerCostume | null {
        return this.#source_costume;
    }

    set source_costume(costume: PickerCostume | null) {
        this.#source_costume = costume;
        this.form.source_costume_id = costume?.id ?? null;
    }

    get target_costume(): PickerCostume | null {
        return this.#target_costume;
    }

    set target_costume(costume: PickerCostume | null) {
        this.#target_costume = costume;
        this.form.target_costume_id = costume?.id ?? null;
    }

    get can_submit(): boolean {
        return this.#source_costume !== null
            && this.#target_costume !== null
            && this.#source_costume.id !== this.#target_costume.id;
    }

    submit = (e: Event) => {
        e.preventDefault();

        const source = this.#source_costume;
        const target = this.#target_costume;

        if (!source || !target) {
            return;
        }

        const question = `Merge "${source.name}" into "${target.name}"? `
            + `"${source.name}" will be permanently deleted.`;

        if (!confirm(question)) {
            return;
        }

        this.form.post(getRoute("admin.costumes.merge"), {
            onSuccess: () => {
                this.source_costume = null;
                this.target_costume = null;
            },
        });
    };
}
