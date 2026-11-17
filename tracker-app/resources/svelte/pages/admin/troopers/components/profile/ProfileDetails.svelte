<script lang="ts">
    import { t } from "$lib/i18n";
    import ConfirmationModal from "$lib/components/ConfirmationModal.svelte";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { getRoute } from "$lib/utils";
    import { untrack } from "svelte";
    import {
        ProfileDetailsViewModel,
        type ProfileDetailsPageData,
    } from "../../models";

    interface Props {
        trooper_id: number;
        details: ProfileDetailsPageData;
    }
    let { trooper_id, details }: Props = $props();

    let vm = new ProfileDetailsViewModel(
        untrack(() => trooper_id),
        untrack(() => details),
    );
</script>

<SlimView>
    {#if details.is_invalid}
        <Alert type="danger">{t("This trooper was created in error (invalid).")}</Alert
        >
    {/if}
    {#if details.can_unvoid}
        <Button
            label={t("Restore")}
            click={() => vm.action("unvoid")}
            btnclass="btn-secondary"
        />
    {/if}
    {#if details.is_departed}
        <Alert type="secondary">{t("This trooper is departed (R.I.P.).")}</Alert>
    {/if}
    {#if details.can_unmark_rip}
        <Button
            label={t("Unmark R.I.P.")}
            click={() => vm.action("unrip")}
            btnclass="btn-secondary"
        />
    {/if}
    {#if details.is_untrusted}
        <Alert type="warning">{t("Untrusted: :reason", { reason: details.untrusted_reason ?? "" })}</Alert>
    {/if}

    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText
                label={t("Legal Name")}
                bind:value={vm.form.legal_name}
                errors={vm.errors.legal_name}
            />
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Display Name")}
                bind:value={vm.form.display_name}
                errors={vm.errors.display_name}
            />
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Email")}
                bind:value={vm.form.email}
                errors={vm.errors.email}
            />
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Phone")}
                bind:value={vm.form.phone}
                errors={vm.errors.phone}
            />
        </InputContainer>
        <InputContainer>
            <InputSelect
                label={t("Status")}
                options={details.membership_status_options}
                bind:value={vm.form.membership_status}
                errors={vm.errors.membership_status}
            />
        </InputContainer>
        <p class="text-muted">{t("Last active: :date", { date: details.last_active_at ?? "" })}</p>
        <SubmitButtonContainer>
            <span class="float-start">
                <a
                    href={getRoute("service-records.trooper", {
                        trooper: trooper_id,
                    })}
                    class="btn btn-outline-info mb-2"
                >
                    {t("Service Record")}
                </a>
            </span>
            <SubmitButton label={t("Update")} submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>

    <hr />

    {#if details.can_mark_untrusted}
        <h5 class="mt-4">{t("Trust Issues")}</h5>
        {#if details.is_untrusted}
            <Button
                label={t("Clear Untrusted")}
                click={vm.clearUntrusted}
                btnclass="btn-secondary"
            />
        {:else}
            <form onsubmit={vm.markUntrusted}>
                <InputContainer>
                    <InputText
                        label={t("Reason")}
                        bind:value={vm.untrusted.untrusted_reason}
                        errors={vm.untrusted.errors.untrusted_reason}
                    />
                </InputContainer>
                <InputContainer>
                    <InputCheckbox
                        label={t("Review costumes")}
                        bind:checked={vm.untrusted.review_costumes}
                    />
                </InputContainer>
                <SubmitButtonContainer>
                    <SubmitButton
                        label={t("Mark Untrusted")}
                        danger={true}
                        submitting={vm.untrusted.processing}
                    />
                </SubmitButtonContainer>
            </form>
        {/if}
        <hr />
    {/if}

    {#if details.can_mark_rip && !details.is_invalid}
        <div class="row mb-3">
            <div class="col text-end">
                <Button
                    label={t("Mark R.I.P.")}
                    click={() => vm.action("rip")}
                    btnclass="btn-outline-secondary"
                />
            </div>
        </div>
    {/if}
    {#if details.can_void && !details.is_departed}
        <div class="row mb-3">
            <div class="col text-end">
                <Button
                    label={t("Void (created in error)")}
                    click={() => vm.action("void")}
                    btnclass="btn-outline-danger"
                />
            </div>
        </div>
    {/if}

    <TrooperStamps trooper_stamps={details.trooper_stamps} />
</SlimView>

<ConfirmationModal
    show={vm.is_confirming}
    title={vm.confirm_prompt.title}
    message={vm.confirm_prompt.message}
    confirmLabel={vm.confirm_prompt.label}
    danger={true}
    onConfirm={vm.confirm}
    onCancel={vm.cancel}
/>
