import type { Trooper } from "$lib/components/models/TrooperPickerViewModel.svelte";
import { SubmitableViewModel, type Option } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { PermissionRequest } from "./PendingPermissionRequestViewModel.svelte";

export type RequestableOrganization = {
    id: number;
    name: string;
    parent_name: string | null;
    node_path: string;
    depth: number;
};

export type RequestPermissionsPageData = {
    organizations: RequestableOrganization[];
    permission_requests: PermissionRequest[];
};

/** A form section a plugin adds, rendered on the server for the picked trooper. */
export type PermissionExtensionSection = {
    key: string;
    html: string;
};

type ExtensionInputs = Record<string, Record<string, string | string[]>>;

export type TrooperPermissionsLookup = {
    trooper: {
        id: number;
        display_name: string;
        legal_name: string;
        membership_role: string | null;
        membership_role_label: string | null;
    };
    moderated_organization_ids: number[];
    covered_organization_ids: number[];
    extension_sections: PermissionExtensionSection[];
};

type RequestPermissionsFormData = {
    trooper_id: string | number | null;
    membership_role: "" | "moderator" | "member";
    moderated_organization_ids: number[];
    note: string | null;
    extensions: ExtensionInputs;
};

function createRequestPermissionsForm(): InertiaForm<RequestPermissionsFormData> {
    return useForm<RequestPermissionsFormData>({
        trooper_id: null,
        membership_role: "",
        moderated_organization_ids: [],
        note: null,
        extensions: {},
    });
}

function toggle(ids: number[], id: number): number[] {
    return ids.includes(id) ? ids.filter((i) => i !== id) : [...ids, id];
}

/**
 * Reads the inputs of the plugins' form sections, which are named
 * "extensions[key][field]" or "extensions[key][field][]".
 */
function collectExtensionInputs(container: HTMLElement | null): ExtensionInputs {
    const inputs: ExtensionInputs = {};
    const fields = container?.querySelectorAll<HTMLInputElement>("input, select, textarea") ?? [];

    for (const field of fields) {
        const match = /^extensions\[([^\]]+)\]\[([^\]]+)\](\[\])?$/.exec(field.name);
        const unchecked = (field.type === "checkbox" || field.type === "radio") && !field.checked;

        if (!match || field.disabled || unchecked) {
            continue;
        }

        const [, key, name, is_list] = match;
        const section = (inputs[key] ??= {});

        if (is_list) {
            section[name] = [...((section[name] as string[] | undefined) ?? []), field.value];
        } else {
            section[name] = field.value;
        }
    }

    return inputs;
}

function isDescendant(child: RequestableOrganization, parent: RequestableOrganization): boolean {
    return child.id !== parent.id && child.node_path.startsWith(parent.node_path);
}

export class RequestPermissionsViewModel extends SubmitableViewModel<
    RequestPermissionsViewModel,
    RequestPermissionsFormData
> {
    organizations: RequestableOrganization[];
    permission_requests: PermissionRequest[];

    /** The element holding the plugins' form sections. */
    extensions_element = $state<HTMLElement | null>(null);

    #trooper = $state<Trooper | null | undefined>(null);
    lookup = $state<TrooperPermissionsLookup | null>(null);
    looking_up = $state(false);

    constructor(pageData: RequestPermissionsPageData) {
        super();
        this.organizations = pageData.organizations ?? [];
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
        this.form.extensions = {};
        this.lookup = null;

        if (trooper) {
            this.lookupTrooper(trooper.id);
        }
    }

    get current_role(): string | null {
        return this.lookup?.trooper.membership_role ?? null;
    }

    get can_change_role(): boolean {
        return (
            this.lookup !== null &&
            this.current_role !== "administrator" &&
            this.current_role !== "manager"
        );
    }

    get role_options(): Option[] {
        const options: Option[] = [{ value: "", label: "No change" }];

        const is_moderator = this.current_role === "moderator";

        options.push({
            value: "moderator",
            label: is_moderator ? "Moderator (change organizations)" : "Make moderator",
        });

        if (is_moderator) {
            options.push({ value: "member", label: "Set back to regular trooper" });
        }

        return options;
    }

    /** Organizations the trooper moderates directly that this requester can change. */
    get current_moderated_organizations(): RequestableOrganization[] {
        const ids = this.lookup?.moderated_organization_ids ?? [];
        return this.organizations.filter((o) => ids.includes(o.id));
    }

    /**
     * Covered by moderation the requester cannot change (granted outside their
     * scope), so it is shown locked rather than as an editable checkbox.
     */
    isModerating(organization: RequestableOrganization): boolean {
        const covered = this.lookup?.covered_organization_ids ?? [];

        return (
            covered.includes(organization.id) &&
            !this.current_moderated_organizations.some(
                (o) => o.id === organization.id || isDescendant(organization, o),
            )
        );
    }

    willBeRemoved(organization: RequestableOrganization): boolean {
        return (
            this.current_moderated_organizations.some((o) => o.id === organization.id) &&
            !this.form.moderated_organization_ids.includes(organization.id) &&
            !this.isIncludedByParent(organization)
        );
    }

    isIncludedByParent(organization: RequestableOrganization): boolean {
        return this.organizations.some(
            (parent) =>
                parent.id !== organization.id &&
                this.form.moderated_organization_ids.includes(parent.id) &&
                isDescendant(organization, parent),
        );
    }

    toggleOrganization(id: number) {
        const ids = toggle(this.form.moderated_organization_ids, id);
        const picked = this.organizations.find((o) => o.id === id);

        // Picking a parent covers its children, so drop any children picked earlier.
        this.form.moderated_organization_ids =
            picked && ids.includes(id)
                ? ids.filter((i) => {
                      const org = this.organizations.find((o) => o.id === i);
                      return !org || org.id === id || !isDescendant(org, picked);
                  })
                : ids;
    }

    /** Validation errors for the plugins' form sections. */
    get extension_errors(): string[] {
        return Object.entries(this.errors)
            .filter(([field]) => field.startsWith("extensions."))
            .flatMap(([, messages]) => messages);
    }

    lookupTrooper = async (trooper_id: string | number) => {
        this.looking_up = true;

        const url = getRoute('admin.troopers.permissions.lookup', { trooper: trooper_id });

        try {
            this.lookup = await fetch(url).then((res) => res.json());
            // Start from what the trooper moderates today so a request can add or remove.
            this.form.moderated_organization_ids = this.current_moderated_organizations.map(
                (o) => o.id,
            );
        } finally {
            this.looking_up = false;
        }
    };

    submit = (e: Event) => {
        e.preventDefault();

        const url = getRoute('admin.troopers.permissions.request');

        this.form.extensions = collectExtensionInputs(this.extensions_element);

        this.form.post(url, { preserveScroll: true, preserveState: 'errors' });
    };
}
