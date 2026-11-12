import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ProfileDetailsPageData, ProfilePageData } from "../profile-types";

export type ProfileDetailsForm = {
    legal_name: string;
    display_name: string;
    email: string;
    phone: string;
    membership_status: string;
};

export type UntrustedForm = {
    untrusted_reason: string;
    review_costumes: boolean;
};

export type ProfileAction = "void" | "unvoid" | "rip" | "unrip";

const ACTION_PROMPTS: Record<ProfileAction, { title: string; message: string; label: string }> = {
    void: {
        title: "Void trooper",
        message: "Mark this trooper as created in error? They will not be able to log in.",
        label: "Void",
    },
    unvoid: {
        title: "Restore trooper",
        message: "Restore this trooper so they can log in again?",
        label: "Restore",
    },
    rip: {
        title: "Mark R.I.P.",
        message: "Mark this trooper as departed? Their service record will show a memorial tribute.",
        label: "Mark R.I.P.",
    },
    unrip: {
        title: "Unmark R.I.P.",
        message: "Remove the R.I.P. status from this trooper?",
        label: "Unmark R.I.P.",
    },
};

function toForm(details: ProfileDetailsPageData): ProfileDetailsForm {
    return {
        legal_name: details.legal_name,
        display_name: details.display_name,
        email: details.email,
        phone: details.phone ?? "",
        membership_status: details.membership_status,
    };
}

export class ProfileDetailsViewModel extends SubmitableViewModel<ProfileDetailsViewModel, ProfileDetailsForm> {
    untrusted = useForm<UntrustedForm>({ untrusted_reason: "", review_costumes: false });
    pending_action: ProfileAction | null = $state(null);

    constructor(
        public readonly trooper_id: number,
        details: ProfileDetailsPageData,
    ) {
        super();
        this.form = useForm<ProfileDetailsForm>(toForm(details));
    }

    get is_confirming(): boolean {
        return this.pending_action !== null;
    }

    get confirm_prompt() {
        return ACTION_PROMPTS[this.pending_action ?? "void"];
    }

    submit = (e: Event) => {
        e.preventDefault();
        this.form.post(
            getRoute("admin.troopers.update-profile", { trooper: this.trooper_id }),
            this.reloadOptions(),
        );
    };

    markUntrusted = (e: Event) => {
        e.preventDefault();
        this.untrusted.post(
            getRoute("admin.troopers.untrusted", { trooper: this.trooper_id }),
            this.reloadOptions(),
        );
    };

    clearUntrusted = () => {
        this.untrusted.post(
            getRoute("admin.troopers.trusted", { trooper: this.trooper_id }),
            this.reloadOptions(),
        );
    };

    action = (action: ProfileAction) => {
        this.pending_action = action;
    };

    cancel = () => {
        this.pending_action = null;
    };

    confirm = () => {
        const action = this.pending_action;
        this.pending_action = null;

        if (action === null) {
            return;
        }

        router.post(
            getRoute(`admin.troopers.${action}`, { trooper: this.trooper_id }),
            {},
            this.reloadOptions(),
        );
    };

    // Void, R.I.P. and the untrusted actions change the trooper's status on the server, so
    // reload the form from the fresh details; a stale status would undo them on the next save.
    private reloadOptions() {
        return createPartialReloadOptions({
            onSuccess: (page) => this.sync(page.props.results as ProfilePageData | undefined),
        });
    }

    private sync(results: ProfilePageData | undefined) {
        const details = results?.details;

        if (!details) {
            return;
        }

        this.form.defaults(toForm(details)).reset();
        this.untrusted.reset();
    }
}
