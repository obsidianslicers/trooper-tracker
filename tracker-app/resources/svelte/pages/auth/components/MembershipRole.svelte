<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputHelp from "$lib/components/form/InputHelp.svelte";
    import InputSelect from "$lib/components/form/InputSelect.svelte";
    import type { RegisterViewModel } from "$lib/domains/auth";

    interface PageProps {
        vm: RegisterViewModel;
    }

    let { vm }: PageProps = $props();
</script>

<InputContainer>
    <InputSelect
        bind:value={vm.form.account_type}
        errors={vm.errors.account_type}
        label="Account Type"
        options={vm.membership_roles}
        placeholder="Select your Account Type"
    />

    {#if vm.form.account_type == "visitor"}
        <InputHelp>
            Visitors are assigned to the top-level organization only. Region
            and/or unit selections are not required.
        </InputHelp>
    {/if}

    {#if vm.form.account_type == "handler"}
        <InputHelp>
            Handlers are assigned to a specific unit and assist with managing an
            event or taking care of specific tasks during the event. Handlers
            are not a verifiable member of one of the listed Star Wars costuming
            organizations, but are affiliated with a unit through their handler
            role.
        </InputHelp>
    {/if}

    {#if vm.form.account_type == "member"}
        <InputHelp>
            Members are assigned to a specific unit and are the troopers of the
            event. Members are a verifiable member of one of the listed Star
            Wars costuming organizations.
        </InputHelp>
    {/if}
</InputContainer>
