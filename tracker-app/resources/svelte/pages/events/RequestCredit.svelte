<!--
    Request Troop Credit: a trooper who trooped a completed event without signing up finds it
    by date or title, then tells us the shift, costume, clubs and details that prove it.
    Plugins can fill the "no match" slot under the results.
-->
<script lang="ts">
    import { t } from "$lib/i18n";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import PluginSlot from "$lib/components/PluginSlot.svelte";
    import InputDate from "$lib/components/form/InputDate.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputCheckbox from "$lib/components/form/InputCheckbox.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { usePage } from "@inertiajs/svelte";
    import { RequestCreditViewModel } from "./models";
    import type { RequestCreditPageData } from "./models";

    const page = usePage<RequestCreditPageData>();

    pageState.title = t("Request Troop Credit");

    const vm = new RequestCreditViewModel(() => page.props);

    const event = $derived(page.props.event);
    const options = $derived(page.props.credit_options);
    const costume_options = $derived([
        { value: null as unknown as number, label: t("No costume / not sure") },
        ...(options?.costumes ?? []).map((costume) => ({ value: costume.id, label: costume.name })),
    ]);
    const shift_options = $derived((event?.shifts ?? []).map((shift) => ({ value: shift.id, label: shift.label })));
</script>

<SlimView wide={true}>
    {#if event === null}
        <p>{t("Trooped an event but never signed up for it in the tracker? Find the event and ask for credit.")}</p>

        <form class="row g-2 mb-3" onsubmit={vm.lookup}>
            <div class="col-12 col-md-5">
                <InputDate label={t("When was the troop?")} bind:value={vm.date} />
            </div>
            <div class="col-12 col-md-5">
                <InputText label={t("Or search by title or venue")} bind:value={vm.search} />
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-lg">{t("Find")}</button>
            </div>
        </form>

        {#if page.props.searched}
            {#if page.props.events.length > 0}
                <p class="mb-2">{t("Pick your event:")}</p>
                <div class="list-group mb-3">
                    {#each page.props.events as match (match.id)}
                        <button
                            type="button"
                            class="list-group-item list-group-item-action"
                            disabled={!match.can_request}
                            onclick={() => vm.pick(match.id)}
                        >
                            <span class="fw-semibold d-block">{match.name}</span>
                            <span class="small text-muted">
                                {match.date}{#if match.venue}&nbsp;· {match.venue}{/if}
                            </span>
                            {#if !match.can_request}
                                <span class="small d-block">{t("You already have credit or a request waiting for this event.")}</span>
                            {/if}
                        </button>
                    {/each}
                </div>
            {:else}
                <p class="text-muted">{t("No completed events match.")}</p>
            {/if}

            {#if page.props.no_match_html}
                <PluginSlot name="events.troop-correction.no-match" html={page.props.no_match_html} />
            {:else}
                <p class="small text-muted">{t("Can't find your troop? Contact command staff.")}</p>
            {/if}
        {/if}
    {:else}
        <div class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                <div>
                    <span class="fw-semibold d-block">{event.name}</span>
                    <span class="small text-muted">
                        {event.date}{#if event.venue}&nbsp;· {event.venue}{/if}
                    </span>
                </div>
                <button type="button" class="btn btn-link btn-sm ms-auto" onclick={vm.clearEvent}>
                    {t("Pick a different event")}
                </button>
            </div>
        </div>

        {#if !event.can_request}
            <x-message type="info" icon="fa-solid fa-circle-info">
                {t("You already have credit or a request waiting for this event.")}
            </x-message>
        {:else}
            <form onsubmit={vm.submit}>
                {#if shift_options.length > 1}
                    <div class="mb-3">
                        <InputSelect label={t("Shift")} options={shift_options}
                                     bind:value={vm.form.event_shift_id} errors={vm.errors.event_shift_id} />
                    </div>
                {/if}

                <div class="mb-3">
                    <InputSelect label={t("Costume")} options={costume_options}
                                 bind:value={vm.form.costume_id} onchange={vm.costumeChanged}
                                 errors={vm.errors.costume_id} />
                </div>

                {#if vm.clubs.length > 1}
                    <fieldset class="mb-3">
                        <legend class="fs-6">{t("Which clubs should get credit?")}</legend>
                        {#each vm.clubs as club (club.id)}
                            <InputCheckbox
                                label={club.name}
                                checked={vm.form.organization_ids.includes(club.id)}
                                onchange={() => vm.toggleClub(club.id, !vm.form.organization_ids.includes(club.id))}
                            />
                        {/each}
                        {#if vm.errors.organization_ids}
                            <div class="invalid-feedback d-block">{vm.errors.organization_ids}</div>
                        {/if}
                    </fieldset>
                {/if}

                <div class="mb-3">
                    <label class="form-label" for="correction-details">
                        {t("Mention any details about the event that prove you attended.")}
                    </label>
                    <textarea
                        id="correction-details"
                        class={["form-control", vm.errors.details && "is-invalid"]}
                        rows="5"
                        placeholder={t("For example: who else trooped, what you wore, something that happened, or a link to photos.")}
                        bind:value={vm.form.details}
                    ></textarea>
                    <InputError errors={vm.errors.details} />
                </div>

                <SubmitButtonContainer>
                    <SubmitButton label={t("Request credit")} submitting={vm.submitting} />
                </SubmitButtonContainer>
            </form>
        {/if}
    {/if}
</SlimView>
