import { t } from "$lib/i18n";
import { ViewModel } from "$lib/domains/types.svelte";
import { createPartialReloadOptions, getRoute } from "$lib/utils";
import { router } from "@inertiajs/svelte";
import type { EventUpload } from "../types";

export type UploadAction = "move" | "delete";

/**
 * The Administrative Uploads and Mission Review tabs: upload photos, move a photo
 * between the two, or delete it. Each acts right away.
 */
export class UploadsViewModel extends ViewModel {
    uploading = $state(false);
    dragging = $state(false);
    pending: { upload: EventUpload; action: UploadAction } | null = $state(null);

    constructor(
        public readonly event_id: number,
        public readonly is_administrative: boolean,
    ) {
        super();
    }

    get move_label(): string {
        return this.is_administrative ? t("Send to Mission Review") : t("Move to Admin Uploads");
    }

    get confirm_prompt(): { title: string; message: string; label: string } {
        if (this.pending?.action === "delete") {
            return { title: t("Delete photo"), message: t("Permanently delete this photo?"), label: t("Delete") };
        }

        return {
            title: this.move_label,
            message: this.is_administrative
                ? t("Move this photo to Mission Review? It may become visible in member event photos and the service-record photo gallery.")
                : t("Move this photo to Admin Uploads? It will be removed from Mission Review and member/gallery photo views."),
            label: this.move_label,
        };
    }

    upload = (files: FileList | null) => {
        this.dragging = false;

        if (!files || files.length === 0) {
            return;
        }

        router.post(
            getRoute("admin.events.upload-image", { event: this.event_id }),
            { images: Array.from(files) },
            createPartialReloadOptions({
                forceFormData: true,
                onStart: () => (this.uploading = true),
                onFinish: () => (this.uploading = false),
            }),
        );
    };

    ask = (upload: EventUpload, action: UploadAction) => {
        this.pending = { upload, action };
    };

    cancel = () => {
        this.pending = null;
    };

    confirm = () => {
        const pending = this.pending;
        this.pending = null;

        if (pending === null) {
            return;
        }

        const route = pending.action === "delete"
            ? "admin.events.uploads.delete"
            : "admin.events.uploads.toggle-type";

        router.post(
            getRoute(route, { event: this.event_id, event_upload: pending.upload.id }),
            {},
            createPartialReloadOptions(),
        );
    };
}
