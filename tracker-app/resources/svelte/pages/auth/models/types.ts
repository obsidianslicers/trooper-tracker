export type LoginProvider = { key: string; name: string; icon_url: string };

export type AuthConfiguration = {
    session: { email: string; }
    providers: LoginProvider[];
    required: { key: string; name: string } | null;
    email_password: { enabled: boolean };
};
