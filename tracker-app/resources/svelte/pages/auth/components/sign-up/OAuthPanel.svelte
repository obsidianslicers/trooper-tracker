<script lang="ts">
    import { SignUpViewModel } from "../../models";
    import OAuthLinks from "../OAuthLinks.svelte";

    interface Props {
        vm: SignUpViewModel;
    }

    let { vm }: Props = $props();
</script>

{#if vm.oauth?.providers.length}
    <div class="d-grid gap-2 mb-3">
        <OAuthLinks oauth={vm.oauth} prefix="Sign Up" />
    </div>
{/if}
