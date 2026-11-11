<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import Button from "$lib/components/ui/buttons/Button.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { getRoute } from "$lib/utils";
    import { untrack } from "svelte";
    import { ProfileDetailsViewModel, type ProfileDetailsPageData } from "../../models";

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
        <Alert type="danger">This trooper was created in error (invalid).</Alert>
        <Button label="Restore" click={() => vm.action("unvoid")} btnclass="btn-secondary" />
    {/if}
    {#if details.is_departed}
        <Alert type="secondary">This trooper is departed (R.I.P.).</Alert>
        <Button label="Unmark R.I.P." click={() => vm.action("unrip")} btnclass="btn-secondary" />
    {/if}
    {#if details.is_untrusted}
        <Alert type="warning">Untrusted: {details.untrusted_reason}</Alert>
    {/if}

    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText label="Legal Name" bind:value={vm.form.legal_name} errors={vm.errors.legal_name} />
        </InputContainer>
        <InputContainer>
            <InputText label="Display Name" bind:value={vm.form.display_name} errors={vm.errors.display_name} />
        </InputContainer>
        <InputContainer>
            <InputText label="Email" bind:value={vm.form.email} errors={vm.errors.email} />
        </InputContainer>
        <InputContainer>
            <InputText label="Phone" bind:value={vm.form.phone} errors={vm.errors.phone} />
        </InputContainer>
        <InputContainer>
            <InputSelect
                label="Status"
                options={details.membership_status_options}
                bind:value={vm.form.membership_status}
                errors={vm.errors.membership_status}
            />
        </InputContainer>
        <p class="text-muted">Last active: {details.last_active_at}</p>
        <SubmitButtonContainer>
            <SubmitButton label="Update" submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>

    <p class="mt-3">
        <a href={getRoute("service-records.trooper", { trooper: trooper_id })}>Service Record</a>
    </p>

    {#if details.can_mark_untrusted}
        <h5 class="mt-4">Trust</h5>
        {#if details.is_untrusted}
            <Button label="Clear Untrusted" click={vm.clearUntrusted} btnclass="btn-secondary" />
        {:else}
            <form onsubmit={vm.markUntrusted}>
                <InputContainer>
                    <InputText
                        label="Reason"
                        bind:value={vm.untrusted.untrusted_reason}
                        errors={vm.untrusted.errors.untrusted_reason}
                    />
                </InputContainer>
                <InputContainer>
                    <InputCheckbox label="Review costumes" bind:checked={vm.untrusted.review_costumes} />
                </InputContainer>
                <SubmitButtonContainer>
                    <SubmitButton label="Mark Untrusted" danger={true} submitting={vm.untrusted.processing} />
                </SubmitButtonContainer>
            </form>
        {/if}
    {/if}

    {#if !details.is_departed && !details.is_invalid}
        <div class="mt-4">
            <Button label="Mark R.I.P." click={() => vm.action("rip")} btnclass="btn-outline-secondary" />
            <Button label="Void (created in error)" click={() => vm.action("void")} btnclass="btn-outline-danger" />
        </div>
    {/if}

    <TrooperStamps trooper_stamps={details.trooper_stamps} />
</SlimView>
