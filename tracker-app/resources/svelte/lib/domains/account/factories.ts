import type { Registration } from "./vms/RegisterViewModel.svelte";

export const AuthFactory = {
    registration(options: Partial<Registration> = {}): Registration {
        return {
            email: null,
            password: null,
            display_name: null,
            legal_name: null,
            membership_role: null,
            date_of_birth: null,
            guardian_email: null,
            organizations: {},
            ...options
        };
    },
};