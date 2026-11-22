import { ViewModel } from "$lib/domains/types.svelte";
import { getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { Award, AwardedTrooper, TroopersPageData } from "../types";

export class TroopersViewModel extends ViewModel {
    award: Award;
    troopers: AwardedTrooper[] = $state([]);
    removing: AwardedTrooper | null = $state(null);
    submitting: boolean = $state(false);

    constructor(pageData: TroopersPageData) {
        super();

        this.award = pageData.award;
        this.troopers = pageData.troopers;
    }

    get assign_url(): string {
        return getRoute("admin.awards.troopers.create", { award: this.award.id! });
    }

    confirmRemove = (trooper: AwardedTrooper) => {
        this.removing = trooper;
    };

    cancelRemove = () => {
        this.removing = null;
    };

    remove = () => {
        if (!this.removing) {
            return;
        }

        this.submitting = true;

        router.post(
            getRoute("admin.awards.troopers.destroy", { award: this.award.id! }),
            { remove_trooper_id: this.removing.award_trooper_id },
            {
                preserveScroll: true,
                onSuccess: (page) => {
                    this.troopers = (page.props as unknown as TroopersPageData).troopers;
                },
                onFinish: () => {
                    this.submitting = false;
                    this.removing = null;
                },
            },
        );
    };
}
