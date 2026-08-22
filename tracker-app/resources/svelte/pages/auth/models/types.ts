
export type AuthConfiguration = {
    session: { email: string; }
    xenforo: { name: string; required: boolean; configured: boolean };
    google: { enabled: boolean; configured: boolean };
    email_password: { enabled: boolean };
};