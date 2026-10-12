import type { Trooper } from "$lib/components/models/TrooperPickerViewModel.svelte";
import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { ForumGroup, PermissionRequest } from "./PendingPermissionRequestViewModel.svelte";

export type RequestableOrganization = {
    id: number;
    name: string;
    parent_name: string | null;
    depth: number;
};

export type RequestPermissionsPageData = {
    organizations: RequestableOrganization[];
    is_xenforo_configured: boolean;
    forum_groups: ForumGroup[];
    permission_requests: PermissionRequest[];
};

export type TrooperPermissionsLookup = {
    trooper: {
        id: number;
        display_name: string;
        legal_name: string;
        membership_role: string | null;
        membership_role_label: string | null;
    };
    moderated_organization_ids: number[];
    has_forum_account: boolean;
    forum_group_ids: number[];
};

type RequestPermissionsFormData = {
    trooper_id: string | number | null;
    membership_role: "" | "moderator" | "member";
    moderated_organization_ids: number[];
    forum_group_ids_to_add: number[];
    forum_group_ids_to_remove: number[];
    note: string | null;
};

function createRequestPermissionsForm(): InertiaForm<RequestPermissionsFormData> {
    return useForm<RequestPermissionsFormData>({
        trooper_id: null,
        membership_role: "",
        moderated_organization_ids: [],
        forum_group_ids_to_add: [],
        forum_group_ids_to_remove: [],
        note: null,
    });
}

function toggle(ids: number[], id: number): number[] {
    return ids.includes(id) ? ids.filter((i) => i !== id) : [...ids, id];
}

export class RequestPermissionsViewModel extends SubmitableViewModel<RequestPermissionsViewModel, RequestPermissionsFormData> {
    organizations: RequestableOrganization[];
    forum_groups: ForumGroup[];
    is_xenforo_configured: boolean;
    permission_requests: PermissionRequest[];

    #trooper = $state<Trooper | null | undefined>(null);
    lookup = $state<TrooperPermissionsLookup | null>(null);
    looking_up = $state(false);

    constructor(pageData: RequestPermissionsPageData) {
        super();
        this.organizations = pageData.organizations ?? [];
        this.forum_groups = pageData.forum_groups ?? [];
        this.is_xenforo_configured = pageData.is_xenforo_configured ?? false;
        this.permission_requests = pageData.permission_requests ?? [];
        this.form = createRequestPermissionsForm();
    }

    get trooper(): Trooper | null | undefined {
        return this.#trooper;
    }

    set trooper(trooper: Trooper | null | undefined) {
        this.#trooper = trooper;
        this.form.trooper_id = trooper?.id ?? null;
        this.form.membership_role = "";
        this.form.moderated_organization_ids = [];
        this.form.forum_group_ids_to_add = [];
        this.form.forum_group_ids_to_remove = [];
        this.lookup = null;

        if (trooper) {
            this.lookupTrooper(trooper.id);
        }
    }

    get current_role(): string | null {
        return this.lookup?.trooper.membership_role ?? null;
    }

    get can_change_role(): boolean {
        return this.lookup !== null && this.current_role !== "administrator";
    }

    get role_options(): Option[] {
        const options: Option[] = [{ value: "", label: "No change" }];

        options.push({
            value: "moderator",
            label: this.current_role === "moderator" ? "Moderator (add organizations)" : "Make moderator",
        });

        if (this.current_role === "moderator") {
            options.push({ value: "member", label: "Set back to regular trooper" });
        }

        return options;
    }

    get can_request_forum_groups(): boolean {
        return this.is_xenforo_configured && (this.lookup?.has_forum_account ?? false);
    }

    get current_forum_groups(): ForumGroup[] {
        const ids = this.lookup?.forum_group_ids ?? [];
        return this.forum_groups.filter((g) => ids.includes(g.id));
    }

    get addable_forum_groups(): ForumGroup[] {
        const ids = this.lookup?.forum_group_ids ?? [];
        return this.forum_groups.filter((g) => !ids.includes(g.id));
    }

    isModerating(organization_id: number): boolean {
        return this.lookup?.moderated_organization_ids.includes(organization_id) ?? false;
    }

    toggleOrganization(id: number) {
        this.form.moderated_organization_ids = toggle(this.form.moderated_organization_ids, id);
    }

    toggleGroupToAdd(id: number) {
        this.form.forum_group_ids_to_add = toggle(this.form.forum_group_ids_to_add, id);
    }

    toggleGroupToRemove(id: number) {
        this.form.forum_group_ids_to_remove = toggle(this.form.forum_group_ids_to_remove, id);
    }

    lookupTrooper = async (trooper_id: string | number) => {
        this.looking_up = true;

        const url = getRoute('admin.troopers.permissions.lookup', { trooper: trooper_id });

        try {
            this.lookup = await fetch(url).then((res) => res.json());
        } finally {
            this.looking_up = false;
        }
    };

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute('admin.troopers.permissions.request');

        this.form.post(url, { preserveScroll: true, preserveState: 'errors' });
    };
}
