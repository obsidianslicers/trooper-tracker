<!-- Sends an instant message to everyone going, after a confirmation, since it can't be taken back. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import type { EventDisplayViewModel } from "../../models";

    let { vm, url, going_label }: { vm: EventDisplayViewModel; url: string; going_label: string } = $props();

    let message = $state("");
    let error = $state("");

    const ask = (e: SubmitEvent) => {
        e.preventDefault();
        vm.confirm({
            title: t("Broadcast an Instant Message"),
            message: t("Send this message to everyone going to this event now?"),
            label: t("Send Message"),
            danger: false,
            run: send,
        });
    };

    const send = () => {
        error = "";
        vm.send(url, { message }, {
            only: ["toast", "flash", "errors"],
            onSuccess: () => (message = ""),
            onError: (errors) => (error = errors.message ?? ""),
        });
    };
</script>

<div class="card my-4 mb-4 border-secondary">
    <div class="card-header bg-secondary text-white d-flex align-items-center">
        <b class="mb-0">{t("Broadcast an Instant Message")}</b>
    </div>
    <div class="card-body">
        <p class="small text-muted">
            <i class="fa fa-fw fa-triangle-exclamation text-warning"></i>
            {t("Send an instant notification to all troopers with a \":status\" status across all shifts. Messages are delivered through each subscriber's preferred notification methods, such as mobile alerts and email.", { status: going_label })}
        </p>
        <form onsubmit={ask}>
            <div class="row g-2">
                <div class="col-sm-12">
                    <textarea class={["form-control", error && "is-invalid"]} rows="3"
                              aria-label={t("Message")} bind:value={message}></textarea>
                    {#if error}
                        <div class="invalid-feedback">{error}</div>
                    {/if}
                </div>
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary w-100" disabled={vm.sending || message.trim() === ""}>
                        <i class="fa fa-fw fa-tower-broadcast me-1"></i>
                        {t("Send Message")}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
