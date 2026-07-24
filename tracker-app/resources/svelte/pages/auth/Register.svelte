<script lang="ts">
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimCard from "$lib/components/ui/SlimCard.svelte";
    import {
        type RegisterPageData,
        RegisterViewModel,
    } from "$lib/domains/auth";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import Identity from "./components/register/Identity.svelte";
    import MembershipRole from "./components/register/MembershipRole.svelte";
    import Organizations from "./components/register/Organizations.svelte";
    import RequiresGuardian from "./components/register/RequiresGuardian.svelte";

    const page = usePage<RegisterPageData>();

    pageState.title = "Register";

    const appName = $derived(page.props.config.branding.name as string);

    const vm = new RegisterViewModel(page.props);
</script>

<SlimCard>
    <form onsubmit={vm.submit}>
        <Alert>
            <b>New to the 501st and/or {appName}?</b> Or are you solely a member
            of another organization? Use this form below to start signing up for
            troops.
            <p class="mt-3 mb-0">
                <i>
                    Command Staff will need to approve your account prior to
                    use.
                </i>
            </p>
        </Alert>

        <Identity {vm} />
        <MembershipRole {vm} />

        {#if vm.form.membership_role != null && vm.form.membership_role != "handler"}
            <Organizations {vm} />
            <RequiresGuardian {vm} {appName} />
        {/if}

        <SubmitButtonContainer>
            <SubmitButton
                label="Register"
                submitting={vm.submitting}
                disabled={!vm.dirty}
            />
        </SubmitButtonContainer>
    </form>
</SlimCard>
