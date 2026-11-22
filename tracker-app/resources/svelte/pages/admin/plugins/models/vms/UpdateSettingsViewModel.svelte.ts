import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { useForm } from "@inertiajs/svelte";
import type { PluginSettingField, UpdateSettingsPageData } from "../types";

type SettingsForm = { settings: Record<string, string> };

export class UpdateSettingsViewModel
    extends SubmitableViewModel<UpdateSettingsViewModel, SettingsForm>
    implements ISubmitableViewModel<SettingsForm> {

    plugin: UpdateSettingsPageData["plugin"];
    fields: PluginSettingField[];

    constructor(pageData: UpdateSettingsPageData) {
        super();

        this.plugin = pageData.plugin;
        this.fields = pageData.settings;
        this.form = useForm<SettingsForm>({
            settings: Object.fromEntries(pageData.settings.map((field) => [field.key, field.value])),
        });
    }

    errorsFor(field: PluginSettingField): string | string[] {
        return (this.form.errors as Record<string, string>)[`settings.${field.key}`] ?? [];
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form.post(getRoute("admin.plugins.settings.update", { slug: this.plugin.slug }));
    };
}
