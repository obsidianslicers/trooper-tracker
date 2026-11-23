import { SubmitableViewModel, type ISubmitableViewModel, type ITrooperStamps } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router, useForm } from "@inertiajs/svelte";
import type { ICostumeForm, UpdatePageData } from "../types";

export class UpdateViewModel
    extends SubmitableViewModel<UpdateViewModel, ICostumeForm>
    implements ISubmitableViewModel<ICostumeForm> {

    costume_id: number;
    can_delete: boolean;
    organizations: UpdatePageData["organizations"];
    deletion_impact: UpdatePageData["deletion_impact"];
    trooper_stamps: ITrooperStamps | null;
    show_delete: boolean = $state(false);
    deleting: boolean = $state(false);

    constructor(pageData: UpdatePageData) {
        super();

        this.costume_id = pageData.costume.id;
        this.can_delete = pageData.costume.can_delete;
        this.organizations = pageData.organizations;
        this.deletion_impact = pageData.deletion_impact;
        this.trooper_stamps = pageData.costume.trooper_stamps;
        this.show_delete = pageData.confirm_delete;
        this.form = useForm<ICostumeForm>({
            name: pageData.costume.name,
            organization_ids: [...pageData.costume.organization_ids],
        });
    }

    isOffered(organization_id: number): boolean {
        return this.form.organization_ids.includes(organization_id);
    }

    setOffered(organization_id: number, offered: boolean) {
        const others = this.form.organization_ids.filter((id) => id !== organization_id);

        this.form.organization_ids = offered ? [...others, organization_id] : others;
    }

    submit = (e: Event) => {
        e.preventDefault();

        // the update request takes organizations[<id>][selected]=1 for each ticked club
        this.form
            .transform((data) => ({
                name: data.name,
                organizations: Object.fromEntries(
                    data.organization_ids.map((id) => [id, { selected: "1" }]),
                ),
            }))
            .post(getRoute("admin.costumes.update", { costume: this.costume_id }));
    };

    confirmDelete = () => {
        this.show_delete = true;
    };

    cancelDelete = () => {
        this.show_delete = false;
    };

    delete = () => {
        this.deleting = true;

        router.post(getRoute("admin.costumes.destroy", { costume: this.costume_id }), {}, {
            onFinish: () => {
                this.deleting = false;
                this.show_delete = false;
            },
        });
    };
}
