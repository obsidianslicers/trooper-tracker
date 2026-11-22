<!-- Emails the event's coordinator a read-only link to every shift's roster. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import Modal from "$lib/components/ui/Modal.svelte";
    import type { EventDisplayViewModel } from "../../models";

    let { vm, url, show = $bindable(false) }: { vm: EventDisplayViewModel; url: string; show: boolean } = $props();

    let email = $state("");
    let error = $state("");

    const close = () => {
        show = false;
        error = "";
    };

    const send = (e: SubmitEvent) => {
        e.preventDefault();
        error = "";
        vm.send(url, { recipient_email: email }, {
            only: ["toast", "flash", "errors"],
            onSuccess: () => {
                email = "";
                close();
            },
            onError: (errors) => (error = errors.recipient_email ?? ""),
        });
    };
</script>

<Modal bind:show title={t("Share roster")} canClose={close}>
    <p class="text-muted">
        {t("Email the event's coordinator a read-only link to every shift's roster. The link stops working after the event.")}
    </p>
    <form onsubmit={send}>
        <label class="form-label small text-muted mb-1" for="recipient_email">{t("Coordinator's email")}</label>
        <input id="recipient_email" type="email" class={["form-control", error && "is-invalid"]}
               placeholder="coordinator@event.com" bind:value={email} required />
        {#if error}
            <div class="invalid-feedback">{error}</div>
        {/if}
        <div class="d-flex justify-content-end gap-3 align-items-center mt-3">
            <button type="button" class="btn btn-link text-muted" onclick={close}>{t("Never mind")}</button>
            <button type="submit" class="btn btn-primary" disabled={vm.sending || email.trim() === ""}>
                {t("Send Roster Link")}
            </button>
        </div>
    </form>
</Modal>
