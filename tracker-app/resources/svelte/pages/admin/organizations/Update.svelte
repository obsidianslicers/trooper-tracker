<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import InputText from "$lib/components/form/InputText.svelte";
    import SubmitButtonContainer from "$lib/components/form/SubmitButtonContainer.svelte";
    import TrooperStamps from "$lib/components/TrooperStamps.svelte";
    import CancelButton from "$lib/components/ui/buttons/CancelButton.svelte";
    import SubmitButton from "$lib/components/ui/buttons/SubmitButton.svelte";
    import SlimView from "$lib/components/ui/SlimView.svelte";
    import pageState from "$lib/states/page-state.svelte";
    import { getRoute } from "$lib/utils";
    import { usePage } from "@inertiajs/svelte";
    import OrganizationTabs from "./components/OrganizationTabs.svelte";
    import { UpdateViewModel, type UpdatePageData } from "./models";

    const page = usePage<UpdatePageData>();

    pageState.title = t("Update Organization");

    let vm = new UpdateViewModel(page.props);
    let logo_input: HTMLInputElement | undefined = $state();
</script>

<OrganizationTabs organization_id={vm.organization.id} is_club={vm.organization.is_club} active="update" />

<SlimView wide={true}>
    <div class="row">
        <div class="col-md-12 col-lg-8">
            <form onsubmit={vm.submit} novalidate>
                {#if vm.organization.parent}
                    <InputContainer>
                        <InputText
                            label={vm.organization.parent.label}
                            value={vm.organization.parent.name}
                            disabled={true}
                        />
                    </InputContainer>
                {/if}
                <InputContainer>
                    <InputText label={t("Name")} bind:value={vm.form.name} errors={vm.errors.name} />
                </InputContainer>
                {#if vm.synchronizer_options}
                    <InputContainer>
                        <InputSelect
                            label={t("Roster sync")}
                            options={vm.synchronizer_options}
                            bind:value={vm.form.synchronizer}
                            errors={vm.errors.synchronizer}
                        />
                        <InputHelp>
                            {t("Keeps this club's members and costumes in step with the club's own records.")}
                        </InputHelp>
                    </InputContainer>
                {/if}
                {#if page.props.plugin_fields}
                    <div
                        class="plugin-slot"
                        data-plugin-slot="admin.organizations.update.fields"
                        bind:this={vm.plugin_fields}
                    >
                        {@html page.props.plugin_fields}
                    </div>
                {/if}
                <SubmitButtonContainer>
                    <SubmitButton label={t("Update")} submitting={vm.submitting} />
                    <CancelButton href={getRoute("admin.organizations.index")} />
                </SubmitButtonContainer>
            </form>
        </div>
        <div class="col-md-12 col-lg-4 text-center">
            <button
                type="button"
                class="btn btn-link p-0 m-3"
                disabled={vm.uploading}
                onclick={() => logo_input?.click()}
                aria-label={t("Click to Replace Logo")}
            >
                <img
                    src={vm.organization.logo_url}
                    alt={t("Organization Logo")}
                    class="img-fluid"
                    width="128"
                    height="128"
                />
            </button>
            <input
                bind:this={logo_input}
                type="file"
                class="d-none"
                accept="image/*"
                data-testid="logo-input"
                onchange={(e) => vm.uploadLogo(e.currentTarget.files?.[0])}
            />
            <p class="form-help text-muted mb-1">
                {#if vm.uploading}
                    <i class="fa fa-fw fa-spinner fa-spin"></i>
                {/if}
                {t("Click to Replace Logo")}
            </p>
            <InputError errors={vm.logo_errors} />
            <p class="form-help text-center">
                <i class="text-muted">
                    {t("The uploaded image will be resized to large 128x128 and small 32x32.")}
                </i>
            </p>
        </div>
    </div>
    <TrooperStamps trooper_stamps={vm.organization.trooper_stamps} />
</SlimView>
