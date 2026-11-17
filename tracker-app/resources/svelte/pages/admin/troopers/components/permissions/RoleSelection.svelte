<script lang="ts">
    import { t } from "$lib/i18n";
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import type { RequestPermissionsViewModel } from "../../models/vms";

    interface Props {
        vm: RequestPermissionsViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.can_change_role}
    <InputContainer>
        <InputSelect
            label={t("Role")}
            options={vm.role_options}
            bind:value={vm.form.membership_role}
            errors={vm.errors.membership_role}
        />
        <InputHelp>
            {t("Currently:")} <strong>{vm.lookup?.trooper.membership_role_label}</strong>
        </InputHelp>
    </InputContainer>
{:else}
    <p class="text-muted small">
        {t("System Owner and Staff Officer roles cannot be changed with a request.")}
    </p>
{/if}
