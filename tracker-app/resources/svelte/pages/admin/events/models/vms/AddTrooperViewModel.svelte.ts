import type { Trooper } from "$lib/components/models/TrooperPickerViewModel.svelte";
import { ViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { AddTrooperChoices } from "../types";

/**
 * Adds a trooper to one shift's roster: pick the trooper, then their costume, club
 * and (when the shift has them) station.
 */
export class AddTrooperViewModel extends ViewModel {
    selected: Trooper | null = $state(null);
    choices: AddTrooperChoices | null = $state(null);
    costume_id: number | string = $state("");
    organization_id: number | string = $state("");
    event_shift_station_id: number | string = $state("");
    loading = $state(false);
    submitting = $state(false);

    constructor(
        public readonly event_id: number,
        public readonly shift_id: number,
    ) {
        super();
    }

    picked = async (trooper: Trooper) => {
        this.loading = true;

        const url = getRoute("admin.events.troopers.costume-picker", {
            event: this.event_id,
            event_shift: this.shift_id,
        });
        const query = new URLSearchParams({ trooper_id: String(trooper.id) });

        try {
            const response = await fetch(`${url}?${query}`, { headers: { Accept: "application/json" } });
            const choices: AddTrooperChoices = await response.json();

            this.choices = choices;
            this.costume_id = choices.costume_options[0]?.value as number ?? "";
            this.organization_id = choices.organization_options.length === 1
                ? choices.organization_options[0].value as number
                : "";
            this.event_shift_station_id = "";
        } finally {
            this.loading = false;
        }
    };

    confirm = () => {
        if (this.choices === null) {
            return;
        }

        router.post(
            getRoute("admin.events.troopers.add", { event: this.event_id, event_shift: this.shift_id }),
            {
                trooper_id: this.choices.trooper_id,
                costume_id: this.costume_id,
                organization_id: this.organization_id,
                event_shift_station_id: this.event_shift_station_id,
            },
            createPartialReloadOptions({
                onStart: () => (this.submitting = true),
                onFinish: () => (this.submitting = false),
                onSuccess: () => this.cancel(),
            }),
        );
    };

    cancel = () => {
        this.choices = null;
        this.selected = null;
    };
}
