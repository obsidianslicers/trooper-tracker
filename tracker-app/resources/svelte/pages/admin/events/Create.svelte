<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import OrganizationPicker from "$lib/components/OrganizationPicker.svelte";
    import Alert from "$lib/components/ui/Alert.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import { t } from "$lib/i18n";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import EventFields from "./components/EventFields.svelte";
    import type { NewEvent } from "./models/types";
    import { CreateEventViewModel } from "./models/vms/CreateEventViewModel.svelte";

    const page = usePage<NewEvent>();

    pageState.title = t("Create Event");

    let vm = new CreateEventViewModel(page.props);

    function status(value: string): string {
        return page.props.status_options.find((option) => option.value === value)?.label ?? value;
    }

    let cancel_url = $derived(
        getRoute(
            "admin.events.list",
            vm.form.organization_id ? { organization_id: vm.form.organization_id } : {},
        ),
    );
</script>

<SlimView wide={true}>
    <form onsubmit={vm.submit} novalidate>
        <Alert type="info" icon="fa-circle-info">
            {@html t(
                "By default when creating a new event, ALL clubs are defaulted to attend unless otherwise updated. See <b>Character Requests & Attendee Limits</b> section to make adjustments to which clubs can attend.",
            )}
            <br />
            <br />
            {t(
                "All Events start is a :draft status, which means they are not visible for sign up until the status is changed to :open or :locked.",
                { draft: status("draft"), open: status("open"), locked: status("locked") },
            )}
        </Alert>

        <InputContainer>
            <OrganizationPicker
                label={t("Hosting Organization")}
                placeholder={t("Select a Host")}
                options={page.props.host_options}
                bind:value={() => vm.form.organization_id, (v) => (vm.form.organization_id = v as number)}
                errors={vm.errors.organization_id}
            />
        </InputContainer>

        {#if vm.hasHost}
            <InputContainer>
                <div class="btn-group" role="group" aria-label={t("How to fill in the event")}>
                    <input
                        type="radio"
                        class="btn-check"
                        id="mode-email"
                        value="email"
                        bind:group={vm.mode}
                    />
                    <label class="btn btn-outline-primary" for="mode-email">
                        {t("Parse Message")}
                    </label>
                    <input
                        type="radio"
                        class="btn-check"
                        id="mode-manual"
                        value="manual"
                        bind:group={vm.mode}
                    />
                    <label class="btn btn-outline-primary" for="mode-manual">
                        {t("Input Manually")}
                    </label>
                </div>
            </InputContainer>

            {#if vm.mode === "email"}
                <InputContainer>
                    <InputText
                        label={vm.club
                            ? t(":club Message", { club: vm.club })
                            : t("Message to Parse")}
                        multiline={true}
                        style="height: 320px"
                        placeholder={t("Event Name: Name of Event")}
                        bind:value={vm.form.source}
                        errors={vm.errors.source}
                        onchange={vm.parseSource}
                    />
                    <InputHelp>
                        {t(
                            "Paste the request for appearance message here, and the system will attempt to parse the relevant event details based on the selected organization.",
                        )}
                    </InputHelp>
                </InputContainer>
            {:else}
                <EventFields
                    {vm}
                    event_types={page.props.event_types}
                    status_options={page.props.status_options}
                    smilies={page.props.smilies}
                    plugin_fields={page.props.plugin_fields}
                />
            {/if}
        {/if}

        <SubmitButtonContainer>
            <SubmitButton label={t("Create")} submitting={vm.submitting} />
            <CancelButton href={cancel_url} />
        </SubmitButtonContainer>
    </form>
</SlimView>
