import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";

export type ForumGroup = {
    id: number;
    title: string;
    banner_image_url?: string | null;
};

export type PermissionRequestOrganization = {
    id: number;
    name: string;
    parent_name: string | null;
};

export type PermissionRequestTrooper = {
    id: number;
    display_name: string;
    legal_name: string;
    membership_role: string | null;
};

export type PermissionRequest = {
    id: number;
    status: "pending" | "approved" | "denied";
    membership_role: "moderator" | "member" | null;
    membership_role_label: string | null;
    moderated_organizations_to_add: PermissionRequestOrganization[];
    moderated_organizations_to_remove: PermissionRequestOrganization[];
    forum_groups_to_add: ForumGroup[];
    forum_groups_to_remove: ForumGroup[];
    note: string | null;
    denial_reason: string | null;
    created: string | null;
    reviewed: string | null;
    trooper: PermissionRequestTrooper;
    requester: PermissionRequestTrooper | null;
    reviewer: PermissionRequestTrooper | null;
};

export class PendingPermissionRequestViewModel extends ViewModel {
    submitting: boolean = $state(false);
    denying: boolean = $state(false);
    denial_reason: string | null = $state(null);
    error: string | null = $state(null);
    request: PermissionRequest;

    constructor(request: PermissionRequest) {
        super();
        this.request = request;
    }

    approve = () => {
        this.post('admin.troopers.permissions.approve', {});
    };

    deny = (e: Event) => {
        e.preventDefault();
        this.post('admin.troopers.permissions.deny', { denial_reason: this.denial_reason });
    };

    private post(name: string, data: Record<string, unknown>) {
        this.submitting = true;
        this.error = null;

        const url = getRoute(name as any, { permission_request: this.request.id });

        router.post(url, data as any, {
            preserveScroll: true,
            preserveState: 'errors',
            onError: (errors) => {
                this.error = Object.values(errors)[0] ?? 'Something went wrong.';
            },
            onFinish: () => {
                this.submitting = false;
            },
        });
    }
}
