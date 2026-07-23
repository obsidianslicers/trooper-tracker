import type { Login, RegistrationInputs } from './types';

export const AuthFactory = {
    login(options: Partial<Login> = {}): Login {
        return {
            email: '',
            password: '',
            remember_me: false,
            ...options
        };
    },
    registration(options: Partial<RegistrationInputs> = {}): RegistrationInputs {
        return {
            email: null,
            password: null,
            display_name: null,
            legal_name: null,
            account_type: null,
            date_of_birth: null,
            guardian_email: null,
            organizations: {},
            ...options
        };
    },
};