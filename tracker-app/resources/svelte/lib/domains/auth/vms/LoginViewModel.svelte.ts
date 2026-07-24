import { SubmitableViewModel } from "$lib/domains/types.svelte";
import toastStateSvelte from "$lib/states/toast-state.svelte";
import { getRoute } from "$lib/utils";
import { useForm, type InertiaForm } from "@inertiajs/svelte";
import type { AuthConfiguration } from "../types";

function createLoginForm(options: Partial<Login> = {}): InertiaForm<Login> {
    const data = {
        email: '',
        password: '',
        remember_me: false,
        ...options
    };

    return useForm<Login>(data);
}

type Login = {
    email: string;
    password: string;
    remember_me: boolean;
};

export type LoginPageData = {
    oauth: AuthConfiguration;
};

export class LoginViewModel extends SubmitableViewModel<LoginViewModel, Login> {
    oauth: AuthConfiguration | null = $state(null);

    constructor(pageData?: LoginPageData) {
        super();
        // Initialize Inertia's useForm hook directly inside the instance
        this.form = createLoginForm();
        if (pageData) {
            this.oauth = pageData.oauth;
            this.form.defaults();
        }
    }

    submit = async (e: Event) => {
        e.preventDefault();

        const url = getRoute('auth.login');

        const toast = toastStateSvelte.info('Logging in...', { delay: 4000 });

        this.form.post(url, {
            preserveScroll: true,
            onError: () => {
                // Dismiss the loading toast if validation fails on backend
                toastStateSvelte.dismiss(toast);
            }
        });
    };
}