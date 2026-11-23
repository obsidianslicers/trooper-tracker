export type LoginProvider = { key: string; name: string; icon_url: string };

/** The sign-up page's providers (SignUpOptionsResource). */
export type SignUpOptions = {
    providers: LoginProvider[];
    required: { name: string } | null;
};

/** The login page's providers and email/password switch (LoginOptionsResource). */
export type LoginOptions = SignUpOptions & {
    email_password: { enabled: boolean };
};

/** The email a login provider gave, for the registration form (RegistrationSessionResource). */
export type RegistrationSession = {
    session: { email: string | null };
};
