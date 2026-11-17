<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import type { RegisterViewModel } from "../../models";

    interface PageProps {
        vm: RegisterViewModel;
    }

    let { vm }: PageProps = $props();
</script>

<InputContainer>
    <InputSelect
        bind:value={vm.form.membership_role}
        errors={vm.errors.membership_role}
        label={t("Membership Role")}
        options={vm.membership_roles}
        placeholder={t("Select your Account Type")}
    />

    {#if vm.form.membership_role == "visitor"}
        <InputHelp>
            {t("Visitors are assigned to the top-level organization only. Region and/or unit selections are not required.")}
        </InputHelp>
    {/if}

    {#if vm.form.membership_role == "handler"}
        <InputHelp>
            {t("Handlers are assigned to a specific unit and assist with managing an event or taking care of specific tasks during the event. Handlers are not a verifiable member of one of the listed Star Wars costuming organizations, but are affiliated with a unit through their handler role.")}
        </InputHelp>
    {/if}

    {#if vm.form.membership_role == "member"}
        <InputHelp>
            {t("Members are assigned to a specific unit and are the troopers of the event. Members are a verifiable member of one of the listed Star Wars costuming organizations.")}
        </InputHelp>
    {/if}
</InputContainer>
