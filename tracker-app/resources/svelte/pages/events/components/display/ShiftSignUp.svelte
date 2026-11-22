<!--
    Sign up, at the end of the roster, where the new row will land. Pressing it shows what there
    is to choose (costume, preselected when we can guess it; plans where the event allows them;
    club or station when needed) and Confirm. With nothing to choose, it signs up straight away.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import { tick, untrack } from "svelte";
    import { SignUpFormViewModel, type EventDisplayViewModel, type SignUpForm } from "../../models";
    import SignUpChoices from "./SignUpChoices.svelte";

    let { vm, sign_up, shift_id }: { vm: EventDisplayViewModel; sign_up: SignUpForm; shift_id: number } = $props();

    const form = new SignUpFormViewModel(untrack(() => sign_up));
    let box: HTMLDivElement | undefined = $state();

    const confirm = () => vm.send(sign_up.url, form.payload(), { shift_id });

    const start = async () => {
        if (!form.has_choices) {
            confirm();
            return;
        }

        form.open();
        await tick();
        box?.querySelector("select")?.focus();
    };
</script>

<div class="shift-signup" id="shift-signup-{shift_id}">
    {#if !form.is_open}
        <div class="shift-signup__start">
            <button type="button" class="btn btn-success shift-signup__button" disabled={vm.sending} onclick={start}>
                {sign_up.is_full ? t("Join stand-by") : t("Sign Up")}
            </button>
            {#if sign_up.hint}
                <span class="text-muted">{sign_up.hint}</span>
            {/if}
        </div>
    {:else}
        <div class="shift-signup-primary shift-signup-primary--form" bind:this={box}>
            <span class="shift-signup-primary__label">{t("Sign up for this shift")}</span>
            <SignUpChoices {form} key="shift-{shift_id}" />
            <div class="shift-signup__confirm">
                <button type="button" class="btn btn-sm btn-success" disabled={!form.can_confirm || vm.sending}
                        onclick={confirm}>
                    {sign_up.is_full ? t("Confirm stand-by") : t("Confirm")}
                </button>
                <button type="button" class="btn btn-link btn-sm text-muted" onclick={form.close}>
                    {t("Never mind")}
                </button>
            </div>
            {#if sign_up.requires_costume && form.costume_id === ""}
                <span class="small text-muted shift-signup__hint">{t("Pick your costume, then press Confirm.")}</span>
            {/if}
        </div>
    {/if}
</div>
