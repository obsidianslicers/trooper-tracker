import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { ProfileCostumesPageData } from "../profile-types";

type CostumeReviewAction = "approve" | "deny" | "revoke" | "undo-revoke";

export class ProfileCostumesViewModel {
    pending_id: number | null = $state(null);
    pending_action: CostumeReviewAction | null = $state(null);

    constructor(public readonly costumes: ProfileCostumesPageData) {}

    get is_confirming(): boolean {
        return this.pending_action !== null;
    }

    get confirm_title(): string {
        return this.pending_action === "undo-revoke" ? "Undo revoke" : "Revoke costume";
    }

    get confirm_message(): string {
        return this.pending_action === "undo-revoke"
            ? "Are you sure you want to undo the revoke of this costume?"
            : "Are you sure you want to revoke this costume?";
    }

    approve = (trooper_costume_id: number) => this.post(trooper_costume_id, "approve");

    deny = (trooper_costume_id: number) => this.post(trooper_costume_id, "deny");

    revoke = (trooper_costume_id: number) => this.ask(trooper_costume_id, "revoke");

    undoRevoke = (trooper_costume_id: number) => this.ask(trooper_costume_id, "undo-revoke");

    confirm = () => {
        if (this.pending_id !== null && this.pending_action !== null) {
            this.post(this.pending_id, this.pending_action);
        }
        this.cancel();
    };

    cancel = () => {
        this.pending_id = null;
        this.pending_action = null;
    };

    private ask(trooper_costume_id: number, action: CostumeReviewAction) {
        this.pending_id = trooper_costume_id;
        this.pending_action = action;
    }

    private post(trooper_costume_id: number, action: CostumeReviewAction) {
        router.post(
            getRoute(`admin.troopers.costumes.${action}`, { trooper_costume: trooper_costume_id }),
            {},
            // Keep the open tab: the costume commands redirect back to this page.
            { preserveScroll: true, preserveState: true },
        );
    }
}
