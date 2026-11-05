<!--
    Form sections plugins add for the picked trooper, such as forum groups.
    The server renders their HTML; the view model reads their inputs on submit.
-->
<script lang="ts">
    import InputContainer from "$lib/components/form/InputContainer.svelte";
    import InputError from "$lib/components/form/InputError.svelte";
    import type { RequestPermissionsViewModel } from "../../models/vms";

    interface Props {
        vm: RequestPermissionsViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.lookup?.extension_sections.length}
    <div bind:this={vm.extensions_element}>
        {#each vm.lookup.extension_sections as section (section.key)}
            <InputContainer>
                <div data-permission-extension={section.key}>
                    {@html section.html}
                </div>
            </InputContainer>
        {/each}
        <InputError errors={vm.extension_errors} />
    </div>
{/if}
