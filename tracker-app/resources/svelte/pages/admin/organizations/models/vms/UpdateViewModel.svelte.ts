import { SubmitableViewModel, type ISubmitableViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { IOrganizationForm, UpdatePageData } from "../types";

export class UpdateViewModel
    extends SubmitableViewModel<UpdateViewModel, IOrganizationForm>
    implements ISubmitableViewModel<IOrganizationForm> {

    organization: UpdatePageData["organization"];
    synchronizer_options: UpdatePageData["synchronizer_options"];
    plugin_fields: HTMLElement | null = $state(null);
    uploading: boolean = $state(false);
    logo_errors: string[] = $state([]);

    constructor(pageData: UpdatePageData) {
        super();

        this.organization = pageData.organization;
        this.synchronizer_options = pageData.synchronizer_options;
        this.form = useForm<IOrganizationForm>({
            name: pageData.organization.name,
            synchronizer: pageData.organization.synchronizer ?? "",
        });
    }

    submit = (e: Event) => {
        e.preventDefault();

        this.form
            .transform((data) => ({ ...data, ...this.pluginValues() }))
            .post(getRoute("admin.organizations.update", { organization: this.organization.id }));
    };

    uploadLogo = (file: File | undefined) => {
        if (!file) {
            return;
        }

        this.uploading = true;
        this.logo_errors = [];

        router.post(
            getRoute("admin.organizations.image.update", { organization: this.organization.id }),
            { logo: file },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                only: ["organization", "toast", "flash"],
                onSuccess: (page) => {
                    this.organization = (page.props as unknown as UpdatePageData).organization;
                },
                onError: (errors) => {
                    this.logo_errors = errors.logo ? [errors.logo] : [];
                },
                onFinish: () => {
                    this.uploading = false;
                },
            },
        );
    };

    /** Fields plugins add to the form come as HTML; send their values with the form. */
    private pluginValues(): Record<string, string> {
        const values: Record<string, string> = {};
        const inputs = this.plugin_fields?.querySelectorAll<HTMLInputElement>("[name]") ?? [];

        inputs.forEach((input) => {
            if ((input.type === "checkbox" || input.type === "radio") && !input.checked) {
                return;
            }
            values[input.name] = input.value;
        });

        return values;
    }
}
