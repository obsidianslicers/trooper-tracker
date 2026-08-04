<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import type { Details } from "$lib/domains/account";
    import { DetailsViewModel } from "$lib/domains/account";

    interface Props {
        details: Details;
    }
    let { details }: Props = $props();

    let vm = new DetailsViewModel(details);
</script>

<SlimView>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <InputText
                label="Legal Name"
                bind:value={vm.form.legal_name}
                errors={vm.errors.legal_name}
            />
            <InputHelp>
                Used for official records or communications with event staff and
                coordinators for safety and accountability reasons. This will
                not be displayed publicly (anonymous viewers).
                <br />
                <i> (i.e. Clark Kent) </i>
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <InputText
                label="Display Name"
                bind:value={vm.form.display_name}
                errors={vm.errors.display_name}
            />
            <InputHelp>
                The name that will be shown publicly (anonymous viewers).
                <br />
                <i> (i.e. Superman) </i>
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <InputText
                label="Phone (optional)"
                bind:value={vm.form.phone}
                errors={vm.errors.phone}
            />
            <InputHelp>
                Your contact phone number. Visible to command staff only.
                <br />
                <i> (i.e. 555-555-5555) </i>
            </InputHelp>
        </InputContainer>
        {#if vm.form.display_costumes && vm.form.display_costumes.length > 0}
            <InputContainer>
                <InputSelect
                    label="Preferred 501st Display Format"
                    bind:value={vm.form.display_costume_id}
                    options={vm.form.display_costumes}
                    errors={vm.errors.display_costume_id}
                />
                <InputHelp>
                    Choose which prefix and ID to display on the forum (e.g.
                    TK52233 or SL52233). Leave blank to use the first costume
                    automatically.
                </InputHelp>
            </InputContainer>
        {/if}
        <InputContainer>
            <InputSelect
                label="Theme"
                bind:value={vm.form.theme}
                options={vm.form.themes}
                errors={vm.errors.theme}
            />
            <InputHelp>
                Customize the look and feel of the Tracker. This will change the
                color scheme and fonts of the interface.
            </InputHelp>
        </InputContainer>
        <SubmitButtonContainer>
            <SubmitButton
                label="Save"
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
</SlimView>
