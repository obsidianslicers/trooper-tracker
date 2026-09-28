<script lang="ts">
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";

    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import { RenewVisitorViewModel, type RenewVisitorPageData } from "./models";

    const page = usePage<RenewVisitorPageData>();

    pageState.title = "Renew Visitor Status";

    let vm = new RenewVisitorViewModel(page.props);

    const app_name = $derived(page.props.config.branding.name as string);
</script>

<SlimView>
    <h2 class="h4 mb-3">Your visitor access has expired</h2>

    <x-message type="warning" icon="fa-solid fa-clock">
        Your 6-month visitor access window ended on
        <strong>{vm.visitor_expires_at}</strong>.
    </x-message>

    <p class="mt-3">
        To continue using {app_name}, submit a renewal request below. Command
        Staff will review your request and restore your access if approved.
    </p>

    <form onsubmit={vm.renew}>
        <SubmitButtonContainer>
            <SubmitButton label="Request Renewal" submitting={vm.submitting} />
        </SubmitButtonContainer>
    </form>
</SlimView>
