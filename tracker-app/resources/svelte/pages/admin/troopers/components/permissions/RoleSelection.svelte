<script lang="ts">
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
            label="Role"
            options={vm.role_options}
            bind:value={vm.form.membership_role}
            errors={vm.errors.membership_role}
        />
        <InputHelp>
            Currently: <strong>{vm.lookup?.trooper.membership_role_label}</strong>
        </InputHelp>
    </InputContainer>
{:else}
    <p class="text-muted small">
        Administrator roles cannot be changed with a request.
    </p>
{/if}
