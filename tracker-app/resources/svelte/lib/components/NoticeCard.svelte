<script lang="ts">
    import { t } from "$lib/i18n";
    import type { UnreadNotice } from "$lib/domains/types.svelte";

    let { notice, ondismiss }: { notice: UnreadNotice; ondismiss: () => void } = $props();

    const icons: Record<UnreadNotice["type"], string> = {
        success: "fa-circle-check",
        info: "fa-circle-info",
        warning: "fa-circle-exclamation",
        danger: "fa-circle-xmark",
    };
</script>

<div class="alert alert-{notice.type} position-relative shadow-sm" role="status">
    <h5 class="alert-heading">
        <i class="fa fa-fw fa-solid {icons[notice.type]}"></i>
        {notice.heading}
    </h5>
    <button
        type="button"
        class="btn-close position-absolute top-0 end-0 m-2 p-2"
        aria-label={t("Mark as read")}
        onclick={ondismiss}
    ></button>
    <p class="fw-bold py-2 border-top border-bottom">{notice.title}</p>
    <div class="notice-message">{@html notice.html}</div>
</div>
