<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperPicker from "$lib/components/TrooperPicker.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import Loading from "$lib/components/ui/Loading.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import ForumGroupSelection from "./components/permissions/ForumGroupSelection.svelte";
    import OrganizationSelection from "./components/permissions/OrganizationSelection.svelte";
    import RequestHistory from "./components/permissions/RequestHistory.svelte";
    import RoleSelection from "./components/permissions/RoleSelection.svelte";
    import { RequestPermissionsViewModel, type RequestPermissionsPageData } from "./models";

    pageState.title = "Request Permissions";

    const page = usePage<RequestPermissionsPageData>();

    const vm = new RequestPermissionsViewModel(page.props);
</script>

<SlimCard>
    <p class="text-muted">
        Ask an administrator to change a trooper's role{vm.is_xenforo_configured
            ? " or forum user groups"
            : ""}. Nothing changes until an administrator approves it.
    </p>
    <form onsubmit={vm.submit}>
        <InputContainer>
            <TrooperPicker
                bind:selected={vm.trooper}
                onSelected={(t) => (vm.trooper = t)}
                errors={vm.errors.trooper_id}
                label="Trooper"
            />
        </InputContainer>

        {#if vm.looking_up}
            <Loading />
        {:else if vm.lookup}
            <RoleSelection {vm} />

            {#if vm.form.membership_role === "moderator"}
                <OrganizationSelection {vm} />
            {/if}

            {#if vm.is_xenforo_configured}
                <ForumGroupSelection {vm} />
            {/if}

            <InputContainer>
                <InputText
                    label="Note for the administrator"
                    multiline={true}
                    bind:value={vm.form.note}
                    errors={vm.errors.note}
                />
                <InputHelp>
                    Explain what this trooper will be doing so the approving
                    administrator has the context.
                </InputHelp>
            </InputContainer>

            <SubmitButtonContainer>
                <SubmitButton
                    label="Submit Request"
                    submitting={vm.submitting}
                />
            </SubmitButtonContainer>
        {/if}
    </form>
</SlimCard>

<RequestHistory requests={vm.permission_requests} />
