<script lang="ts">
    import { t } from "$lib/i18n";
    import { getRoute } from "$lib/utils";
    import type { ListEventsViewModel } from "../models";

    let { vm }: { vm: ListEventsViewModel } = $props();

    const icons: Record<string, string> = {
        success: "fa-circle-check",
        info: "fa-circle-info",
        warning: "fa-circle-exclamation",
        danger: "fa-circle-xmark",
    };
</script>

{#if vm.notices.notice}
    {@const notice = vm.notices.notice}
    <div class="alert alert-{notice.type} position-relative shadow-sm" role="status">
        <h5 class="alert-heading">
            <i class="fa fa-fw fa-solid {icons[notice.type]}"></i>
            {notice.heading}
        </h5>
        <button
            type="button"
            class="btn-close position-absolute top-0 end-0 m-2 p-2"
            aria-label={t("Mark as read")}
            onclick={vm.dismissNotice}
        ></button>
        <p class="fw-bold py-2 border-top border-bottom">{notice.title}</p>
        <div class="notice-message">{@html notice.html}</div>
    </div>
{:else if vm.notices.count > 1}
    <div class="alert alert-info" role="status">
        <i class="fa fa-fw fa-solid fa-circle-info"></i>
        <a href={getRoute("account.notices")}>
            {t("You have :count unread notices.", { count: vm.notices.count })}
        </a>
    </div>
{/if}
