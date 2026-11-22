import { t } from "$lib/i18n";
import type { FriendForm } from "../display-types";
import { SignUpFormViewModel } from "./SignUpFormViewModel.svelte";

/**
 * Add a friend: once a trooper is picked in the search, their choices (club, station, costume)
 * load, and Confirm signs them up for the shift.
 */
export class AddFriendViewModel {
    friend: FriendForm | null = $state(null);
    form: SignUpFormViewModel | null = $state(null);
    is_loading = $state(false);
    error = $state("");

    constructor(private readonly form_url: string) {}

    picked = async (trooper: { id: string | number }) => {
        this.is_loading = true;
        this.error = "";

        try {
            const url = `${this.form_url}?trooper_id=${encodeURIComponent(String(trooper.id))}`;
            const response = await fetch(url, { headers: { Accept: "application/json" } });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            this.friend = await response.json() as FriendForm;
            this.form = new SignUpFormViewModel(this.friend, true);
        } catch {
            this.error = t("Unable to load that trooper's choices right now.");
        } finally {
            this.is_loading = false;
        }
    };

    payload(): Record<string, unknown> {
        return this.form?.payload({ trooper_id: this.friend?.trooper.id }) ?? {};
    }

    reset = () => {
        this.friend = null;
        this.form = null;
        this.error = "";
    };
}
