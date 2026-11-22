<!--
    The event's details, shared by Create Event and the event's Overview tab.
-->
<script lang="ts">
    import AccordionPanel from "$lib/components/AccordionPanel.svelte";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputMarkdown from "$lib/components/form/InputMarkdown.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import type { Option, Smiley } from "$lib/domains/types.svelte";
    import { t } from "$lib/i18n";
    import type { EventFormViewModel, EventTypeOption } from "../models";
    import OrganizationLimits from "./OrganizationLimits.svelte";
    import YesNo from "./YesNo.svelte";

    interface Props {
        vm: EventFormViewModel<unknown>;
        event_types: EventTypeOption[];
        status_options: Option[];
        smilies: Smiley[];
        plugin_fields: string;
        /** Who can run the event; left out where the creator always does (a new event). */
        coordinator_options?: Option[] | null;
    }
    let {
        vm,
        event_types,
        status_options,
        smilies,
        plugin_fields,
        coordinator_options = null,
    }: Props = $props();

    let event_type = $derived(
        event_types.find((type) => type.value == vm.form.event_type_id),
    );
</script>

    <InputContainer>
        <InputText
            label={t("Name")}
            bind:value={vm.form.name}
            errors={vm.errors.name}
        />
    </InputContainer>
    <InputContainer>
        <InputSelect
            label={t("Event Type")}
            options={event_types}
            bind:value={vm.form.event_type_id}
            errors={vm.errors.event_type_id}
        />
        {#if event_type?.description}
            <InputHelp><i>{event_type.description}</i></InputHelp>
        {/if}
        {#if event_type && !event_type.is_counted}
            <div class="form-text text-warning px-2">
                <i class="fa fa-fw fa-circle-info"></i>
                {t(
                    "Non-troop-credit event: Does not count toward troop totals.",
                )}
            </div>
        {/if}
    </InputContainer>
    {#if coordinator_options}
        <InputContainer>
            <InputSelect
                label={t("Event Coordinator")}
                options={coordinator_options}
                bind:value={vm.form.coordinator_trooper_id}
                errors={vm.errors.coordinator_trooper_id}
            />
            <InputHelp>
                {t(
                    "Shown on the event page, and they can edit this event. It's whoever created the event unless you pick an event coordinator or command staff member who is going or attended. If their status changes to anything else, it goes back to the creator. The forum thread stays under the creator's name.",
                )}
            </InputHelp>
        </InputContainer>
    {/if}
    <InputContainer>
        <InputSelect
            label={t("Status")}
            options={status_options}
            bind:value={vm.form.status}
            errors={vm.errors.status}
        />
    </InputContainer>

    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <InputDate
                label={t("Starts")}
                with_time={true}
                placeholder={null}
                bind:value={vm.form.event_start}
                errors={vm.errors.event_start}
            />
        </div>
        <div class="col-12 col-md-6 mb-3">
            <InputDate
                label={t("Ends")}
                with_time={true}
                placeholder={null}
                bind:value={vm.form.event_end}
                errors={vm.errors.event_end}
            />
        </div>
    </div>

    <InputContainer>
        <InputText
            label={t("Website")}
            bind:value={vm.form.event_website}
            errors={vm.errors.event_website}
        />
    </InputContainer>

    <AccordionPanel label={t("Location (Mission Coordinates)")}>
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Latitude")}
                    bind:value={vm.form.latitude}
                    errors={vm.errors.latitude}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Longitude")}
                    bind:value={vm.form.longitude}
                    errors={vm.errors.longitude}
                />
            </div>
        </div>
        <InputContainer>
            <InputText
                label={t("Venue")}
                bind:value={vm.form.venue}
                errors={vm.errors.venue}
            />
        </InputContainer>
        <InputText
            label={t("Venue Address")}
            bind:value={vm.form.venue_address}
            errors={vm.errors.venue_address}
        />
        <InputHelp>
            {t(
                "The full address of the venue, including street, city, state, and zip.",
            )}
        </InputHelp>
    </AccordionPanel>

    <AccordionPanel label={t("Contact Information (Point of Contact)")}>
        <InputContainer>
            <InputText
                label={t("Contact Name")}
                bind:value={vm.form.contact_name}
                errors={vm.errors.contact_name}
            />
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Contact Phone")}
                bind:value={vm.form.contact_phone}
                errors={vm.errors.contact_phone}
            />
        </InputContainer>
        <InputText
            label={t("Contact Email")}
            bind:value={vm.form.contact_email}
            errors={vm.errors.contact_email}
        />
    </AccordionPanel>

    <AccordionPanel
        label={t("Character Requests & Attendee Limits (Trooper Quotas)")}
    >
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Expected Attendees")}
                    bind:value={vm.form.expected_attendees}
                    errors={vm.errors.expected_attendees}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Requested Number of Characters")}
                    bind:value={vm.form.requested_number_characters}
                    errors={vm.errors.requested_number_characters}
                />
            </div>
        </div>
        <InputContainer>
            <InputText
                label={t("Requested Character Types")}
                multiline={true}
                style="height: 128px"
                bind:value={vm.form.requested_character_types}
                errors={vm.errors.requested_character_types}
            />
        </InputContainer>
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Maximum Shift Sign-Ups (per event)")}
                    placeholder={t("blank=unlimited")}
                    bind:value={vm.form.shifts_allowed}
                    errors={vm.errors.shifts_allowed}
                />
                {#if !vm.form.shifts_allowed}
                    <InputHelp>
                        {t("blank=unlimited")}
                    </InputHelp>
                {/if}
            </div>
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Tentative Sign-Ups Allowed (per shift)")}
                    bind:value={vm.form.tentative_signups_allowed}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Trooper Capacity (per shift)")}
                    placeholder={t("blank=unlimited")}
                    bind:value={vm.form.troopers_allowed}
                    errors={vm.errors.troopers_allowed}
                />
                {#if !vm.form.troopers_allowed}
                    <InputHelp>
                        {t("blank=unlimited")}
                    </InputHelp>
                {/if}
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Handler Capacity (per shift)")}
                    placeholder={t("blank=unlimited, zero=none")}
                    bind:value={vm.form.handlers_allowed}
                    errors={vm.errors.handlers_allowed}
                />
                {#if !vm.form.handlers_allowed}
                    <InputHelp>
                        {t("blank=unlimited, zero=none")}
                    </InputHelp>
                {/if}
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Limit Add a Trooper Sign-Ups (per shift)")}
                    placeholder={t("blank=unlimited, zero=none")}
                    bind:value={vm.form.friends_allowed}
                    errors={vm.errors.friends_allowed}
                />
                {#if !vm.form.friends_allowed}
                    <InputHelp>
                        {t("blank=unlimited, zero=none")}
                    </InputHelp>
                {/if}
            </div>
            <div class="col-12 col-md-6 mb-3">
                <InputText
                    label={t("Limit Add a Guest Sign-Ups (per shift)")}
                    placeholder={t("blank=unlimited, zero=none")}
                    bind:value={vm.form.guests_allowed}
                    errors={vm.errors.guests_allowed}
                />
                {#if !vm.form.guests_allowed}
                    <InputHelp>
                        {t("blank=unlimited, zero=none")}
                    </InputHelp>
                {/if}
            </div>
        </div>
        <OrganizationLimits {vm} />
    </AccordionPanel>

    <AccordionPanel
        label={t("Venue Permissions & Amenities (Logistics & Support)")}
    >
        <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Secure Staging Area")}
                    bind:value={vm.form.secure_staging_area}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Parking Available")}
                    bind:value={vm.form.parking_available}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Allow Blasters")}
                    bind:value={vm.form.allow_blasters}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Allow Props")}
                    bind:value={vm.form.allow_props}
                />
            </div>
            <div class="col-12 col-md-6 mb-3">
                <YesNo
                    label={t("Limited Mobility Accessible")}
                    bind:value={vm.form.accessible}
                />
            </div>
        </div>
        <InputText
            label={t("Amenities")}
            multiline={true}
            style="height: 384px"
            bind:value={vm.form.amenities}
            errors={vm.errors.amenities}
        />
    </AccordionPanel>

    <AccordionPanel label={t("Miscellaneous (Mission Brief)")}>
        <InputContainer>
            <InputMarkdown
                label={t("Comments")}
                smilies={smilies}
                bind:value={vm.form.comments}
                errors={vm.errors.comments}
            />
        </InputContainer>
        <InputContainer>
            <YesNo
                label={t(
                    "Require Mission Brief Acknowledgement Before Signup",
                )}
                bind:value={vm.form.require_mission_brief_ack}
            />
            <InputHelp>
                {t(
                    'When set to "Yes", troopers must explicitly acknowledge that they have read the mission brief on the event page before any sign-up buttons are shown.',
                )}
            </InputHelp>
        </InputContainer>
        <InputContainer>
            <InputText
                label={t("Referred By")}
                bind:value={vm.form.referred_by}
                errors={vm.errors.referred_by}
            />
        </InputContainer>
        {#if plugin_fields}
            <div
                class="plugin-slot"
                data-plugin-slot="admin.events.form.fields"
                bind:this={vm.plugin_fields}
            >
                {@html plugin_fields}
            </div>
        {/if}
    </AccordionPanel>
