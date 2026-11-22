import { t } from "$lib/i18n";
import { SubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ListPageData, PluginRow } from "../types";

type InstallForm = { archive: File | null };

type PendingAction = {
    route: string;
    plugin: PluginRow;
    message: string;
    label: string;
    danger: boolean;
};

export class ListViewModel extends SubmitableViewModel<ListViewModel, InstallForm> {
    plugins: PluginRow[] = $state([]);
    pending: PendingAction | null = $state(null);
    working: string | null = $state(null);

    constructor(pageData: ListPageData) {
        super();

        this.plugins = pageData.plugins;
        this.form = useForm<InstallForm>({ archive: null });
    }

    get is_confirming(): boolean {
        return this.pending !== null;
    }

    /** Enable, Disable or Cancel; turning on a plugin that can do things asks first. */
    toggle = (plugin: PluginRow) => {
        if (plugin.toggle === "enable" && plugin.enable_confirm) {
            this.pending = {
                route: "admin.plugins.enable",
                plugin,
                message: plugin.enable_confirm,
                label: t("Enable"),
                danger: false,
            };
            return;
        }

        this.send(plugin.toggle === "enable" ? "admin.plugins.enable" : "admin.plugins.disable", plugin);
    };

    confirmUninstall = (plugin: PluginRow) => {
        this.pending = {
            route: "admin.plugins.uninstall",
            plugin,
            message: t("Uninstall :plugin? This deletes its data and settings.", { plugin: plugin.name }),
            label: t("Uninstall"),
            danger: true,
        };
    };

    confirm = () => {
        if (this.pending) {
            this.send(this.pending.route, this.pending.plugin);
        }
        this.pending = null;
    };

    cancel = () => {
        this.pending = null;
    };

    pickArchive = (file: File | undefined) => {
        this.form.archive = file ?? null;
    };

    install = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.plugins.install"), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => this.form.reset(),
        });
    };

    private send(route: string, plugin: PluginRow) {
        this.working = plugin.slug;

        router.post(getRoute(route, { slug: plugin.slug }), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                this.plugins = (page.props as unknown as ListPageData).plugins;
            },
            onFinish: () => {
                this.working = null;
            },
        });
    }
}
