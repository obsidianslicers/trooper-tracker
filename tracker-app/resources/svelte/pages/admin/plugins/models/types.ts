export type PluginStatus = {
    label: string;
    tone: "success" | "warning" | "danger" | "info" | "secondary";
    title: string | null;
};

export type PluginRow = {
    slug: string;
    name: string;
    description: string | null;
    author: string | null;
    capabilities: string | null;
    version: string;
    pending_upgrade: string | null;
    source: string;
    status: PluginStatus;
    requirements: { text: string; is_problem: boolean } | null;
    last_error: string | null;
    has_settings: boolean;
    toggle: "enable" | "disable" | "cancel";
    can_enable: boolean;
    enable_confirm: string | null;
    is_installed: boolean;
};

export type MissingPlugin = {
    slug: string;
    details: string;
    last_error: string | null;
};

export type ListPageData = {
    plugins: PluginRow[];
    uploads: { is_enabled: boolean; max_megabytes: string };
    missing: MissingPlugin[];
};

export type PluginSettingField = {
    key: string;
    type: "text" | "url" | "email" | "number" | "boolean" | "secret";
    label: string;
    help: string | null;
    value: string;
    is_saved: boolean;
};

export type UpdateSettingsPageData = {
    plugin: { slug: string; name: string };
    settings: PluginSettingField[];
};
