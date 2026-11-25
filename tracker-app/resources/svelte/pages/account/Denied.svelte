<!-- A denied application: why, the clubs it asked for, and a form to pick clubs and resubmit. -->
<script lang="ts">
    import { t } from "$lib/i18n";
    import OrganizationSelection from "$lib/components/OrganizationSelection.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import type { DeniedPageData } from "./models/onboarding-types";
    import { DeniedViewModel } from "./models/vms/DeniedViewModel.svelte";

    let props: DeniedPageData = $props();

    pageState.title = t("Application Status");

    // svelte-ignore state_referenced_locally
    const vm = new DeniedViewModel(props);
</script>

<SlimView wide>
    <Alert type="danger" icon="fa-solid fa-circle-xmark" classes="w-100">
        <strong>{t("Your registration was not approved.")}</strong>
        {#if props.denial_reason}
            <div class="mt-2">{props.denial_reason}</div>
        {/if}
    </Alert>

    {#if props.denied_requests.length > 0}
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="mb-3">
                    <i class="fa fa-fw fa-circle-xmark text-danger"></i>
                    {t("Denied Club Requests")}
                </h6>
                <p class="text-muted small mb-3">
                    {t("The following club requests were part of your original application.")}
                </p>
                <ul class="list-group list-group-flush">
                    {#each props.denied_requests as request (request.id)}
                        <li class="list-group-item px-0">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-fw fa-circle-xmark text-danger"></i>
                                <span>{request.club ?? "—"}</span>
                            </div>
                        </li>
                    {/each}
                </ul>
            </div>
        </div>
    {/if}

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">{t("Resubmit Your Application")}</h6>
            <p class="text-muted mb-3">
                {t("Update your organization selections below and resubmit for reconsideration. A moderator will review your application and you will be notified of the decision.")}
            </p>

            <form onsubmit={vm.submit} novalidate>
                <OrganizationSelection {vm} role={props.account_type} />

                <SubmitButtonContainer>
                    <SubmitButton label={t("Resubmit Application")} submitting={vm.submitting} />
                </SubmitButtonContainer>
            </form>
        </div>
    </div>
</SlimView>
